<?php

namespace App\Libs\Settings;

use App\Libs\Fields\FieldRegistry;
use App\Libs\Fields\Options\OptionContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;

class SettingStore
{
    public const string CacheKey = 'settings:values';

    public function __construct(private readonly FieldRegistry $fields) {}

    public function get(string $key, mixed $fallback = null): mixed
    {
        $hasFallback = func_num_args() > 1;
        $snapshot = $this->snapshot();
        $definition = $snapshot[$key] ?? null;
        if (! is_array($definition)) {
            return $fallback;
        }
        $payload = is_string($definition['payload'] ?? null) ? $this->decryptArray($definition['payload']) : null;
        if ($payload !== null) {
            [$valid, $value] = $this->typedValue($definition, $payload);
            if ($valid) {
                return $value;
            }
        }
        if ($hasFallback) {
            return $fallback;
        }
        $default = $this->jsonArray($definition['default_value'] ?? null);
        if ($default === null) {
            return null;
        }
        [$valid, $value] = $this->typedValue($definition, $default);

        return $valid ? $value : null;
    }

    public function forget(): void
    {
        Cache::store(config('settings.cache_store'))->forget(self::CacheKey);
    }

    /** @return array<string, array<string, mixed>> */
    private function snapshot(): array
    {
        $cache = Cache::store(config('settings.cache_store'));
        $encrypted = $cache->get(self::CacheKey);
        if (is_string($encrypted)) {
            $snapshot = $this->decryptArray($encrypted);
            if ($snapshot !== null) {
                return $snapshot;
            }
            $cache->forget(self::CacheKey);
        }
        $snapshot = $this->loadSnapshot();
        if ($snapshot === null) {
            return [];
        }
        $cache->put(
            self::CacheKey,
            Crypt::encryptString(json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
            max(30, min(3600, (int) config('settings.cache_ttl', 300))),
        );

        return $snapshot;
    }

    /** @return array<string, array<string, mixed>>|null */
    private function loadSnapshot(): ?array
    {
        try {
            $groups = DB::table('system_setting_groups')->select(['id', 'parent_id', 'active'])->get();
            $activeGroupIds = $this->activeGroupIds($groups->all());
            $rows = DB::table('system_setting_definitions as definitions')
                ->join('system_setting_groups as groups', 'groups.id', '=', 'definitions.group_id')
                ->leftJoin('system_setting_values as values', 'values.setting_definition_id', '=', 'definitions.id')
                ->where('definitions.active', true)
                ->where('groups.active', true)
                ->whereIn('definitions.group_id', $activeGroupIds)
                ->select([
                    'definitions.key as setting_key', 'definitions.input_type', 'definitions.multiple',
                    'definitions.validation', 'definitions.option_source_type', 'definitions.option_source_key',
                    'definitions.options_i18n', 'definitions.default_value', 'values.payload',
                ])
                ->get();
        } catch (QueryException $exception) {
            if (! $this->isMissingTable($exception)) {
                throw $exception;
            }

            return null;
        }
        $snapshot = [];
        foreach ($rows as $row) {
            $definition = (array) $row;
            $key = $definition['setting_key'] ?? null;
            if (is_string($key) && $key !== '') {
                unset($definition['setting_key']);
                $snapshot[$key] = $definition;
            }
        }

        return $snapshot;
    }

    /** @param list<object> $groups @return list<int> */
    private function activeGroupIds(array $groups): array
    {
        $byId = [];
        foreach ($groups as $group) {
            $byId[(int) $group->id] = $group;
        }
        $active = [];
        foreach ($groups as $group) {
            $current = $group;
            $seen = [];
            while ($current !== null) {
                $id = (int) $current->id;
                if (isset($seen[$id]) || ! $current->active) {
                    break;
                }
                $seen[$id] = true;
                if ($current->parent_id === null) {
                    $active[] = (int) $group->id;
                    break;
                }
                $current = $byId[(int) $current->parent_id] ?? null;
            }
        }

        return $active;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $payload
     * @return array{bool, mixed}
     */
    private function typedValue(array $definition, array $payload): array
    {
        $inputType = $definition['input_type'] ?? '';
        if (! is_string($inputType) || ! $this->fields->has($inputType)) {
            return [false, null];
        }
        $field = $this->fields->get($inputType);
        $multiple = (bool) ($definition['multiple'] ?? false);
        $storageKey = match ($field->dataType($multiple)) {
            'text', 'select' => 'value_string',
            'localized_text' => 'value_i18n',
            'number' => 'value_number',
            'boolean' => 'value_boolean',
            'multi_select' => 'value_json',
            default => null,
        };
        if ($storageKey === null || ! array_key_exists($storageKey, $payload)) {
            return [false, null];
        }
        $value = [$storageKey => $payload[$storageKey]];
        try {
            $validation = $this->fields->normalizeValidation($field, $multiple, $this->jsonArray($definition['validation'] ?? null) ?? []);
        } catch (InvalidArgumentException) {
            return [false, null];
        }
        $optionsI18n = $this->jsonArray($definition['options_i18n'] ?? null) ?? [];
        try {
            $options = $field->usesOptions($multiple)
                ? $this->fields->optionSources()->resolve(
                    (string) ($definition['option_source_type'] ?? 'manual'),
                    $definition['option_source_key'] ?? null,
                    $optionsI18n,
                    new OptionContext(app()->getLocale(), 'runtime'),
                )
                : [];
        } catch (InvalidArgumentException) {
            return [false, null];
        }
        $validator = $field->validator($multiple);
        if ($storageKey === 'value_string' && $inputType === 'text' && is_string($value[$storageKey]) && trim($value[$storageKey]) === '' && ($validation['min_length'] ?? 0) === 0) {
            return [true, ''];
        }
        if ($storageKey === 'value_number' && (! is_numeric($value[$storageKey]) || ! is_finite((float) $value[$storageKey]))) {
            return [false, null];
        }
        if (! $validator->validate($value, $options, $validation)) {
            return [false, null];
        }
        $filtered = $validator->filter($value, $options, $validation);

        return [true, $filtered[$storageKey] ?? null];
    }

    /** @return array<string, mixed>|null */
    private function decryptArray(string $encrypted): ?array
    {
        try {
            return $this->jsonArray(Crypt::decryptString($encrypted));
        } catch (DecryptException) {
            return null;
        }
    }

    /** @return array<string, mixed>|null */
    private function jsonArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value)) {
            return null;
        }
        try {
            $decoded = json_decode($value, true, 128, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function isMissingTable(QueryException $exception): bool
    {
        $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($state, ['42S02', '42P01'], true)
            || $state === 'HY000' && str_contains(strtolower($exception->getMessage()), 'no such table');
    }
}

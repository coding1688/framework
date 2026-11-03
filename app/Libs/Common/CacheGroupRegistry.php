<?php

namespace App\Libs\Common;

use InvalidArgumentException;

final class CacheGroupRegistry
{
    /** @var array<string, array<string, string>> */
    private array $groups = [];

    /** @var array<string, array{group: string, name: array<string, string>, description: array<string, string>, store: string, ttl: int, invalidator: callable, browser: callable, rebuilder: callable}> */
    private array $entries = [];

    /** @param array<string, string> $name */
    public function registerGroup(string $key, array $name): void
    {
        $this->validateKey($key);
        $this->validateTranslations($name);
        if (isset($this->groups[$key])) {
            throw new InvalidArgumentException('Cache group is already registered.');
        }
        $this->groups[$key] = $name;
    }

    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>  $description
     */
    public function register(string $key, string $group, array $name, array $description, string $store, int $ttl, callable $invalidator, callable $browser, callable $rebuilder): void
    {
        $this->validateKey($key);
        $this->validateTranslations($name);
        $this->validateTranslations($description, false);
        if (! isset($this->groups[$group]) || isset($this->entries[$key]) || $ttl < 0 || trim($store) === '') {
            throw new InvalidArgumentException('Invalid cache entry registration.');
        }
        $this->entries[$key] = compact('group', 'name', 'description', 'store', 'ttl', 'invalidator', 'browser', 'rebuilder');
    }

    public function forget(string $key): void
    {
        ($this->entry($key)['invalidator'])();
    }

    /** @return list<array{key: string, label: string, exists: bool}> */
    public function search(string $key, ?string $search = null): array
    {
        return ($this->entry($key)['browser'])($search);
    }

    public function hasKey(string $key, string $cacheKey): bool
    {
        foreach ($this->search($key) as $entry) {
            if ($entry['key'] === $cacheKey) {
                return true;
            }
        }

        return false;
    }

    public function refresh(string $key, string $cacheKey): void
    {
        ($this->entry($key)['invalidator'])($cacheKey);
    }

    public function rebuild(string $key, string $cacheKey): void
    {
        ($this->entry($key)['rebuilder'])($cacheKey);
    }

    /** @return array<string, mixed> */
    private function entry(string $key): array
    {
        return $this->entries[$key] ?? throw new InvalidArgumentException('Unknown cache entry.');
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->entries);
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        $groups = [];
        foreach ($this->groups as $key => $name) {
            $groups[$key] = ['key' => $key, 'name' => $this->label($name), 'entries' => []];
        }
        foreach ($this->entries as $key => $entry) {
            $groups[$entry['group']]['entries'][] = [
                'key' => $key,
                'name' => $this->label($entry['name']),
                'description' => $this->label($entry['description']),
                'store' => $entry['store'],
                'ttl' => $entry['ttl'],
            ];
        }

        return array_values(array_filter($groups, fn (array $group): bool => $group['entries'] !== []));
    }

    private function validateKey(string $key): void
    {
        if (preg_match('/^[a-z][a-z0-9]*(?:\.[a-z][a-z0-9_-]*)*$/D', $key) !== 1) {
            throw new InvalidArgumentException('Invalid cache registry key.');
        }
    }

    /** @param array<string, string> $translations */
    private function validateTranslations(array $translations, bool $required = true): void
    {
        if ($required && (! is_string($translations['en'] ?? null) || trim($translations['en']) === '')) {
            throw new InvalidArgumentException('Cache labels require English.');
        }
        foreach ($translations as $label) {
            if (! is_string($label)) {
                throw new InvalidArgumentException('Cache labels must be strings.');
            }
        }
    }

    /** @param array<string, string> $names */
    private function label(array $names): string
    {
        $fallback = function_exists('config') ? (string) config('app.fallback_locale', 'en') : 'en';
        $locale = function_exists('app') ? app()->getLocale() : 'en';
        foreach (array_unique([$locale, 'en', $fallback, 'zh_CN', ...array_keys($names)]) as $key) {
            if (is_string($names[$key] ?? null) && trim($names[$key]) !== '') {
                return trim($names[$key]);
            }
        }

        return '';
    }
}

<?php

namespace App\Http\Requests\Backend\System;

use App\Http\Requests\Backend\BaseRequest;
use App\Libs\Common\CacheGroupRegistry;
use Closure;
use Illuminate\Validation\Rule;

class SystemCacheRequest extends BaseRequest
{
    public function rules(): array
    {
        $registry = app(CacheGroupRegistry::class);
        $moduleRules = ['required', 'string', 'max:128', Rule::in($registry->keys())];

        return match ($this->route()->getActionMethod()) {
            'keys' => [
                'key' => $moduleRules,
                'search' => ['nullable', 'string', 'max:128'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
            'clear' => ['key' => $moduleRules],
            'refresh', 'rebuild' => [
                'key' => $moduleRules,
                'cache_key' => ['required', 'string', 'max:512', function (string $attribute, mixed $value, Closure $fail) use ($registry): void {
                    $module = $this->input('key');
                    if (is_string($module) && in_array($module, $registry->keys(), true) && ! $registry->hasKey($module, (string) $value)) {
                        $fail(__('validation.exists', ['attribute' => $attribute]));
                    }
                }],
            ],
            default => [],
        };
    }
}

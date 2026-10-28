<?php

namespace App\Libs\Settings\Resolvers;

use App\Libs\Settings\Contracts\SettingResolver;
use App\Libs\Settings\MailConfiguration;

class MailSettingResolver implements SettingResolver
{
    public function __construct(private readonly MailConfiguration $configuration) {}

    public function values(): array
    {
        $mail = $this->configuration->resolve();
        $values = [];
        foreach (['host', 'port', 'encryption', 'username', 'timeout'] as $field) {
            $values['mail.smtp.'.$field] = [in_array($field, ['port', 'timeout'], true) ? 'value_number' : 'value_string' => $mail[$field]];
        }

        return $values + [
            'mail.smtp.enabled' => ['value_boolean' => $mail['enabled']],
            'mail.from.address' => ['value_string' => $mail['from_address']],
            'mail.from.name' => ['value_string' => $mail['from_name']],
        ];
    }

    public function hasValue(string $key): ?bool
    {
        return $key === 'mail.smtp.password' ? filled($this->configuration->resolve()['password']) : null;
    }
}

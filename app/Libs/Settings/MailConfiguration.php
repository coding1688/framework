<?php

namespace App\Libs\Settings;

use Illuminate\Mail\MailManager;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

final class MailConfiguration
{
    public function __construct(private readonly SettingStore $settings) {}

    /** @return array<string, mixed> */
    public function resolve(): array
    {
        $smtp = (array) config('mail.mailers.smtp');
        $port = (int) $this->settings->get('mail.smtp.port', $smtp['port'] ?? 587);

        return [
            'enabled' => $this->settings->get('mail.smtp.enabled') === true,
            'host' => $this->settings->get('mail.smtp.host', $smtp['host'] ?? ''),
            'port' => $port,
            'encryption' => $this->settings->get('mail.smtp.encryption', ($smtp['scheme'] ?? null) === 'smtps' || $port === 465 ? 'smtps' : 'tls'),
            'username' => $this->settings->get('mail.smtp.username', $smtp['username'] ?? ''),
            'password' => $this->settings->get('mail.smtp.password', $smtp['password'] ?? ''),
            'from_address' => $this->settings->get('mail.from.address', config('mail.from.address')),
            'from_name' => $this->settings->get('mail.from.name', config('mail.from.name')),
            'timeout' => (int) $this->settings->get('mail.smtp.timeout', 15),
        ];
    }

    public function apply(MailMessage $message): MailMessage
    {
        $resolved = $this->resolve();
        if (! $resolved['enabled']) {
            return $message;
        }
        foreach (['host', 'port', 'encryption', 'username', 'password', 'timeout'] as $field) {
            if (! self::validValue('mail.smtp.'.$field, $resolved[$field])) {
                throw new InvalidArgumentException(__('backend/common.invalid'));
            }
        }
        foreach (['address' => 'from_address', 'name' => 'from_name'] as $field => $value) {
            if (! self::validValue('mail.from.'.$field, $resolved[$value])) {
                throw new InvalidArgumentException(__('backend/common.invalid'));
            }
        }
        $name = 'framework_smtp';
        config(['mail.mailers.'.$name => [
            'transport' => 'smtp',
            'host' => $resolved['host'], 'port' => $resolved['port'],
            'scheme' => $resolved['encryption'] === 'smtps' ? 'smtps' : 'smtp',
            'auto_tls' => $resolved['encryption'] !== 'none',
            'require_tls' => $resolved['encryption'] === 'tls',
            'username' => $resolved['username'], 'password' => $resolved['password'],
            'timeout' => $resolved['timeout'],
        ]]);
        app(MailManager::class)->purge($name);

        return $message->mailer($name)->from($resolved['from_address'], $resolved['from_name']);
    }

    public static function validValue(string $key, mixed $value): bool
    {
        $rules = match ($key) {
            'mail.smtp.host' => ['required', 'string', 'max:256', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! filter_var($value, FILTER_VALIDATE_IP) && ! preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/iD', $value)) {
                    $fail(__('backend/common.invalid'));
                }
            }],
            'mail.smtp.port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail.smtp.encryption' => ['required', 'in:tls,smtps,none'],
            'mail.smtp.username' => ['nullable', 'string', 'max:256', 'not_regex:/[\r\n\x00]/'],
            'mail.smtp.password' => ['nullable', 'string', 'max:512'],
            'mail.smtp.timeout' => ['required', 'integer', 'min:1', 'max:120'],
            'mail.from.address' => ['required', 'string', 'email:rfc', 'max:256', 'not_regex:/[\r\n\x00]/'],
            'mail.from.name' => ['required', 'string', 'max:128', 'not_regex:/[\p{C}]/u'],
            default => [],
        };

        return Validator::make(['value' => $value], ['value' => $rules])->passes();
    }
}

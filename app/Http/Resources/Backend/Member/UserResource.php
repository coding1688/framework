<?php

namespace App\Http\Resources\Backend\Member;

use App\Services\Member\MemberWalletAmount;
use App\Services\Member\MemberWalletService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $freeze = $this->activeFreeze;
        $currency = $this->default_currency ?: app(MemberWalletService::class)->mainCurrency();
        $balance = $this->wallets->firstWhere('currency', $currency)?->balance ?? MemberWalletAmount::toDecimal(0, $currency);

        return [
            'id' => $this->id, 'username' => $this->username, 'name' => $this->name,
            'email' => $this->email, 'is_test' => $this->is_test, 'email_verified' => $this->hasVerifiedEmail(),
            'remark' => $this->remark, 'locale' => $this->locale ?: config('app.locale'),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'vip_level' => $this->effective_vip_level, 'vip_level_id' => $this->vip_level_id,
            'vip_name' => $this->vipLevel?->name, 'vip_status' => $this->vip_status,
            'vip_started_at' => $this->vip_started_at?->toIso8601String(), 'vip_expires_at' => $this->vip_expires_at?->toIso8601String(),
            'default_currency' => $currency, 'total_balance' => $balance, 'available_balance' => $balance,
            'avatar' => $this->avatar, 'active' => $this->active,
            'frozen_at' => $freeze?->created_at?->toIso8601String(), 'frozen_reason' => $freeze?->reason, 'frozen_by' => $freeze?->created_by,
            'providers' => $this->whenLoaded('socialAccounts', fn () => $this->socialAccounts->pluck('provider')->values()),
            'last_login_at' => $this->last_login_at?->toIso8601String(), 'last_login_ip' => $this->last_login_ip,
            'last_login_device' => $this->last_login_device, 'last_login_device_type' => $this->last_login_device_type,
            'created_at' => $this->created_at?->toIso8601String(),
            $this->mergeWhen($request->route()?->getActionMethod() === 'detail', [
                'password_changed_at' => $this->password_changed_at?->toIso8601String(),
                'locale_options' => config('platform.locales'),
                'currency_options' => array_keys(config('member.wallet.currencies')),
            ]),
        ];
    }
}

<?php

namespace App\Providers;

use App\Libs\Settings\Resolvers\MailSettingResolver;
use App\Libs\Settings\SettingResolverRegistry;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function boot(SettingResolverRegistry $settings, MailSettingResolver $resolver): void
    {
        $settings->register($resolver);
    }
}

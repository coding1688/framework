<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendPageTest extends TestCase
{
    public function test_guest_can_access_the_framework_documentation(): void
    {
        $this->get(route('frontend.home'))->assertOk()
            ->assertSee('<title>开发文档 · ', false)
            ->assertSee('name="description"', false)
            ->assertSee('id="frontend-app"', false)
            ->assertSee('window.frontendConfig', false)
            ->assertSee('oauth', false)
            ->assertSee('google', false)
            ->assertSee('facebook', false);
    }

    public function test_frontend_management_routes_return_the_spa_entry(): void
    {
        foreach (['/manage/account', '/manage/wallet', '/manage/notifications'] as $path) {
            $this->get($path)->assertOk()->assertSee('id="frontend-app"', false);
        }
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendPageTest extends TestCase
{
    public function test_frontend_entry_renders_the_user_application(): void
    {
        $this->get(route('frontend.home'))->assertOk()
            ->assertSee('id="frontend-app"', false)
            ->assertSee('window.frontendConfig', false)
            ->assertSee('oauth', false)
            ->assertSee('google', false)
            ->assertSee('facebook', false);
    }
}

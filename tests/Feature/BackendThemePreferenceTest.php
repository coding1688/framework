<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_preferences_are_available_without_stored_settings(): void
    {
        $admin = new AdminUser;

        $this->assertSame(config('platform.backend_ui.theme.defaults'), $admin->themePreferences());
    }

    public function test_preferences_are_saved_for_the_current_administrator(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => $admin->auth_version]);
        $preferences = ['mode' => 'dark', 'preset' => 'azure', 'density' => 'compact', 'radius' => 'large', 'button_size' => 'large'];

        $this->postJson(route('backend.auth.preferences'), $preferences)
            ->assertOk()
            ->assertJsonPath('data', $preferences);

        $this->assertSame($preferences, $admin->fresh()->themePreferences());
    }

    public function test_invalid_preferences_are_rejected_without_overwriting_existing_values(): void
    {
        $admin = AdminUser::factory()->super()->create([
            'preferences' => ['mode' => 'light', 'preset' => 'emerald', 'density' => 'comfortable', 'radius' => 'medium', 'button_size' => 'default'],
        ]);
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => $admin->auth_version]);

        foreach (['mode', 'preset', 'density', 'radius', 'button_size'] as $field) {
            $this->postJson(route('backend.auth.preferences'), [
                'mode' => 'dark', 'preset' => 'slate', 'density' => 'compact', 'radius' => 'large', 'button_size' => 'large',
                $field => 'invalid',
            ])->assertUnprocessable()->assertJsonValidationErrors($field);
        }

        $this->assertSame(['mode' => 'light', 'preset' => 'emerald', 'density' => 'comfortable', 'radius' => 'medium', 'button_size' => 'default'], $admin->fresh()->themePreferences());
    }
}

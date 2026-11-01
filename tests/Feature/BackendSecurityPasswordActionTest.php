<?php

namespace Tests\Feature;

use App\Attributes\RequireSecurityPassword;
use App\Http\Middleware\Backend\BackendAclMid;
use App\Http\Middleware\Backend\BackendAuthMid;
use App\Http\Middleware\Backend\BackendSecurityPasswordMid;
use App\Libs\Security\SecurityPassword;
use App\Models\Admin\AdminLog;
use App\Models\Admin\AdminUser;
use App\Models\System\SystemSettingDefinition;
use App\Models\System\SystemSettingGroup;
use App\Services\Backend\Common\BackendRoutes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Tests\TestCase;

class BackendSecurityPasswordActionTest extends TestCase
{
    use RefreshDatabase;

    private const SecurityPassword = 'SeparateSafe123';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::store('array')->flush();
        SecurityPasswordActionTestController::$executions = 0;
        Route::domain(config('platform.backend_domain'))->middleware(['web', 'backend.locale', 'backend.log', 'backend.auth', 'backend.securityPassword', 'backend.permission'])->group(function (): void {
            Route::post('/test/security/protected', [SecurityPasswordActionTestController::class, 'protectedAction'])->name('test.security.protected');
            Route::post('/test/security/ordinary', [SecurityPasswordActionTestController::class, 'ordinaryAction'])->name('test.security.ordinary');
        });
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_missing_wrong_login_array_and_query_passwords_cannot_execute_an_annotated_action(): void
    {
        $this->backend();
        foreach ([[], ['security_password' => 'WrongSafe123'], ['security_password' => 'TestingPass123!'], ['security_password' => ['bad']], ['security_password' => str_repeat('x', 129)]] as $payload) {
            $this->postJson(route('test.security.protected'), $payload)->assertUnprocessable()->assertJsonValidationErrors('security_password');
        }
        $this->postJson(route('test.security.protected', ['security_password' => self::SecurityPassword]), [])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->assertSame(0, SecurityPasswordActionTestController::$executions);
    }

    public function test_board_members_still_need_their_own_security_password_and_unconfigured_accounts_are_blocked(): void
    {
        $admin = $this->backend();
        $this->postJson(route('test.security.protected'), ['security_password' => self::SecurityPassword])->assertOk()->assertJsonPath('has_password_field', false);
        $this->assertSame(1, SecurityPasswordActionTestController::$executions);
        $admin->forceFill(['security_password' => null])->save();
        $this->postJson(route('test.security.protected'), ['security_password' => self::SecurityPassword])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->assertSame(1, SecurityPasswordActionTestController::$executions);
    }

    public function test_unannotated_actions_do_not_require_a_security_password(): void
    {
        $this->backend();
        $this->postJson(route('test.security.ordinary'), [])->assertOk();
        $this->assertSame(1, SecurityPasswordActionTestController::$executions);
    }

    public function test_authentication_and_acl_run_before_password_verification(): void
    {
        $this->postJson(route('test.security.protected'), ['security_password' => self::SecurityPassword])->assertUnauthorized();
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => $admin->auth_version]);
        $this->mock(SecurityPassword::class)->shouldNotReceive('assertValid');
        $this->postJson(route('test.security.protected'), ['security_password' => self::SecurityPassword])->assertForbidden();
        $this->assertSame(0, SecurityPasswordActionTestController::$executions);
        $middleware = app('router')->gatherRouteMiddleware(Route::getRoutes()->getByName('test.security.protected'));
        $this->assertLessThan(array_search(BackendSecurityPasswordMid::class, $middleware, true), array_search(BackendAclMid::class, $middleware, true));
        $this->assertLessThan(array_search(BackendAclMid::class, $middleware, true), array_search(BackendAuthMid::class, $middleware, true));
    }

    public function test_password_verification_keeps_exact_spaces_and_limits_failed_attempts_per_account(): void
    {
        $this->freezeTime();
        $admin = $this->backend();
        $admin->forceFill(['security_password' => '  SeparateSafe123  '])->save();
        $this->postJson(route('test.security.protected'), ['security_password' => '  SeparateSafe123  '])->assertOk();
        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('test.security.protected'), ['security_password' => 'WrongSafe123'])->assertUnprocessable();
        }
        $this->postJson(route('test.security.protected'), ['security_password' => '  SeparateSafe123  '])->assertStatus(429)->assertHeader('Retry-After');
        $this->assertSame(1, SecurityPasswordActionTestController::$executions);
    }

    public function test_action_metadata_is_generated_from_attributes_and_not_menu_or_client_flags(): void
    {
        $routes = app(BackendRoutes::class)->all();
        $this->assertSame(['required' => true, 'mode' => 'dialog'], $routes['test.security.protected']['security_password']);
        $this->assertArrayNotHasKey('security_password', $routes['test.security.ordinary']);
        $this->assertSame(['required' => true, 'mode' => 'form'], $routes['system.setting.update']['security_password']);
        $this->assertSame(['required' => true, 'mode' => 'dialog'], $routes['system.setting.status']['security_password']);
        $this->assertSame(['required' => true, 'mode' => 'form'], $routes['development.setting.update']['security_password']);
        $this->assertSame(['required' => true, 'mode' => 'form'], $routes['development.settingGroup.update']['security_password']);
        $this->assertSame(['required' => true, 'mode' => 'form'], $routes['admin.department.status']['security_password']);
        $this->assertSame(['required' => true, 'mode' => 'dialog'], $routes['frontend.user.password']['security_password']);
        $this->assertArrayNotHasKey('security_password', $routes['development.setting.create']);
        $this->backend();
        $this->postJson(route('test.security.protected'), ['requires_security_password' => false, 'security_password_required' => false])->assertUnprocessable()->assertJsonValidationErrors('security_password');
    }

    public function test_normal_group_edits_also_require_action_confirmation_and_failed_verification_does_not_mutate_data(): void
    {
        $this->backend();
        $group = SystemSettingGroup::factory()->create();
        $payload = ['parent_id' => $group->parent_id, 'code' => $group->code, 'name_i18n' => ['en' => 'Renamed group'], 'description_i18n' => $group->description_i18n, 'active' => $group->active, 'sort' => $group->sort];
        $this->postJson(route('development.settingGroup.update', ['id' => $group->id]), $payload)->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->assertNotSame('Renamed group', $group->fresh()->name_i18n['en']);
        $response = $this->postJson(route('development.settingGroup.update', ['id' => $group->id]), [...$payload, 'security_password' => self::SecurityPassword])->assertOk();
        $this->assertSame('Renamed group', $group->fresh()->name_i18n['en']);
        $this->assertStringNotContainsString(self::SecurityPassword, $response->getContent());
    }

    public function test_real_setting_actions_verify_only_once_and_keep_passwords_out_of_audit_data(): void
    {
        $this->backend();
        $definition = SystemSettingDefinition::factory()->create();
        $this->partialMock(SecurityPassword::class)->shouldReceive('assertValid')->once()->passthru();
        $this->postJson(route('system.setting.status', ['id' => $definition->id]), ['active' => false, 'security_password' => self::SecurityPassword])->assertOk();
        $this->assertFalse($definition->fresh()->active);
        $log = AdminLog::where('action', 'system.setting.status')->sole();
        $this->assertStringNotContainsString(self::SecurityPassword, json_encode($log->changes, JSON_THROW_ON_ERROR));
    }

    public function test_native_validation_never_flashes_a_security_password(): void
    {
        $this->backend();
        $this->post(route('test.security.protected'), ['security_password' => 'WrongSafe123', 'marker' => 'visible'])
            ->assertRedirect()->assertSessionHasErrors('security_password')->assertSessionMissing('_old_input.security_password');
    }

    public function test_unknown_input_mode_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RequireSecurityPassword(mode: 'unknown');
    }

    private function backend(): AdminUser
    {
        $admin = AdminUser::factory()->super()->create(['security_password' => self::SecurityPassword]);
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => $admin->auth_version]);

        return $admin;
    }
}

class SecurityPasswordActionTestController
{
    public static int $executions = 0;

    #[RequireSecurityPassword(mode: 'dialog')]
    public function protectedAction(Request $request): JsonResponse
    {
        self::$executions++;

        return response()->json(['ok' => true, 'has_password_field' => $request->exists('security_password')]);
    }

    public function ordinaryAction(): JsonResponse
    {
        self::$executions++;

        return response()->json(['ok' => true]);
    }
}

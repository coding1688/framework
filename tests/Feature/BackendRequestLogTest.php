<?php

namespace Tests\Feature;

use App\Attributes\IgnoreLog;
use App\Http\Middleware\Backend\BackendLogMid;
use App\Models\Admin\AdminUser;
use App\Services\Backend\Common\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class BackendRequestLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_failure_logs_status_without_request_secrets(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        Log::shouldReceive('channel')->once()->with('backend_requests')->andReturn($logger);
        $logger->shouldReceive('info')->once()->with('backend.request', Mockery::on(function (array $context): bool {
            $this->assertSame('backend.auth.login', $context['route']);
            $this->assertSame(422, $context['status']);
            $this->assertSame('POST', $context['method']);
            $this->assertGreaterThanOrEqual(0, $context['duration_ms']);
            $this->assertSame(['route', 'method', 'status', 'duration_ms', 'administrator_id', 'ip'], array_keys($context));

            return true;
        }));
        $this->postJson('http://a.base.local/auth/login', ['password' => 'NeverLogThisSecret'])->assertUnprocessable();
        $this->assertDatabaseCount('admin_logs', 0);
    }

    public function test_unauthorized_request_is_logged_before_auth_rejection(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        Log::shouldReceive('channel')->once()->with('backend_requests')->andReturn($logger);
        $logger->shouldReceive('info')->once()->with('backend.request', Mockery::on(fn (array $context): bool => $context['status'] === 401 && $context['administrator_id'] === null));
        $this->getJson('http://a.base.local/auth/userinfo')->assertUnauthorized();
    }

    public function test_logout_keeps_actor_snapshot_and_single_business_audit(): void
    {
        $admin = AdminUser::factory()->super()->create();
        $this->actingAs($admin, 'backend')->withSession(['backend.auth_version' => $admin->auth_version]);
        $logger = Mockery::mock(LoggerInterface::class);
        Log::shouldReceive('channel')->once()->with('backend_requests')->andReturn($logger);
        $logger->shouldReceive('info')->once()->with('backend.request', Mockery::on(fn (array $context): bool => $context['status'] === 200 && $context['administrator_id'] === $admin->id));
        $this->postJson('http://a.base.local/auth/logout')->assertOk();
        $this->assertDatabaseCount('admin_logs', 1);
        $this->assertDatabaseHas('admin_logs', ['action' => 'auth.logout', 'administrator_id' => $admin->id]);
    }

    public function test_ignore_log_attribute_skips_request_log_but_preserves_explicit_audit(): void
    {
        Route::middleware(['web', BackendLogMid::class])
            ->get('/test-ignore-log', [IgnoredLogController::class, 'ignored']);
        Log::shouldReceive('channel')->never();
        $this->getJson('http://a.base.local/test-ignore-log')->assertOk();
        $this->assertDatabaseHas('admin_logs', ['action' => 'test.explicit.audit']);
    }
}

class IgnoredLogController
{
    #[IgnoreLog]
    public function ignored(): JsonResponse
    {
        Audit::record('test.explicit.audit');

        return response()->json(['ok' => true]);
    }
}

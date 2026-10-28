<?php

namespace Tests\Feature;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminLog;
use App\Models\Admin\AdminMenu;
use App\Models\Admin\AdminUser;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackendDepartmentGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://a.base.local/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function signIn(?AdminUser $actor = null): AdminUser
    {
        $actor ??= AdminUser::where('username', 'admin')->sole();
        $this->actingAs($actor->fresh(), 'backend')->withSession(['backend.auth_version' => $actor->fresh()->auth_version]);

        return $actor;
    }

    private function department(?AdminDepartment $parent = null, ?array $permissions = null): AdminDepartment
    {
        $department = AdminDepartment::create([
            'name_i18n' => ['zh_CN' => fake()->unique()->company()],
            'parent_id' => $parent?->id ?? AdminDepartment::where('code', 'board')->sole()->id,
            'active' => true,
        ]);
        $department->menus()->sync($permissions ?? AdminMenu::pluck('id')->all());
        AdminUser::factory()->create(['department_id' => $department->id, 'is_leader' => true]);

        return $department;
    }

    private function payload(?AdminDepartment $department = null, array $overrides = []): array
    {
        return array_replace([
            'name_i18n' => ['zh_CN' => '运营小组', 'en' => 'Operations team'],
            'active' => true, 'sort' => 0,
            'parent_id' => $department?->parent_id ?? AdminDepartment::where('code', 'board')->sole()->id,
            'menu_ids' => $department?->menus()->pluck('admin_menus.id')->all() ?? [],
            'leader_ids' => $department?->leaders()->pluck('id')->all() ?? [],
        ], $overrides);
    }

    private function employeePayload(AdminUser $target, array $overrides = []): array
    {
        return array_replace([
            'name' => $target->name, 'email' => $target->email, 'locale' => $target->locale,
            'active' => $target->active, 'department_id' => $target->department_id,
        ], $overrides);
    }

    public function test_identity_controls_user_and_department_visibility(): void
    {
        $department = $this->department();
        $child = $this->department($department);
        $other = $this->department();
        $leader = $department->leaders()->sole();
        $employee = AdminUser::factory()->create(['department_id' => $department->id]);
        $this->signIn($leader);
        $users = $this->getJson(self::BASE.'admin/user/list')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([$leader->id, $employee->id], array_column($users, 'id'));
        $this->getJson(self::BASE.'admin/user/list?department_id='.$child->id)->assertOk()->assertJsonCount(0, 'data');
        $departments = $this->getJson(self::BASE.'admin/department/list')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([$department->id, $child->id], array_column($departments, 'id'));

        $this->signIn($employee);
        $this->getJson(self::BASE.'admin/user/list')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $employee->id);
        $this->getJson(self::BASE.'admin/department/list')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $department->id)->assertJsonPath('data.0.can_manage', false)->assertJsonCount(0, 'data.0.leaders');
        $this->getJson(self::BASE.'admin/department/options')->assertOk()->assertJsonCount(0, 'leaders');
        $this->getJson(self::BASE.'admin/user/options')->assertOk()->assertJsonCount(1, 'departments');
        $this->postJson(self::BASE.'admin/user/create', [])->assertForbidden();
        $this->postJson(self::BASE.'admin/user/update/'.$employee->id, [])->assertForbidden();
        $this->postJson(self::BASE.'admin/user/status/'.$leader->id, ['active' => false])->assertForbidden();
        $this->postJson(self::BASE.'admin/user/delete/'.$leader->id)->assertForbidden();
        $this->postJson(self::BASE.'admin/department/create', [])->assertForbidden();
        $this->postJson(self::BASE.'admin/department/permissions/'.$department->id, ['menu_ids' => []])->assertForbidden();
        $this->postJson(self::BASE.'admin/department/delete/'.$department->id)->assertForbidden();

        $this->signIn();
        $this->getJson(self::BASE.'admin/user/list')->assertOk()->assertJsonPath('total', AdminUser::count());
        $this->getJson(self::BASE.'admin/department/list')->assertOk()->assertJsonCount(AdminDepartment::count(), 'data');
    }

    public function test_employee_management_and_permission_assignment_require_their_own_grants(): void
    {
        $parent = $this->department();
        $child = $this->department($parent);
        $employeePermission = AdminMenu::where('permission', 'admin.department.employees')->sole();
        $grantPermission = AdminMenu::where('permission', 'admin.department.permissions')->sole();
        $parent->menus()->detach([$employeePermission->id, $grantPermission->id]);
        $this->signIn($parent->leaders()->sole());
        $this->getJson(self::BASE.'admin/department/list')->assertOk();
        $this->getJson(self::BASE.'admin/department/employees/'.$child->id)->assertForbidden();
        $this->postJson(self::BASE.'admin/department/permissions/'.$child->id, ['menu_ids' => []])->assertForbidden();
        $parent->menus()->attach([$employeePermission->id, $grantPermission->id]);
        $this->getJson(self::BASE.'admin/department/employees/'.$child->id)->assertOk();
        $this->postJson(self::BASE.'admin/department/permissions/'.$child->id, ['menu_ids' => []])->assertOk();
    }

    public function test_superior_can_assign_department_leaders_from_its_employees(): void
    {
        $parent = $this->department();
        $child = $this->department($parent);
        $other = $this->department();
        $superior = $parent->leaders()->sole();
        $leader = $child->leaders()->sole();
        $employee = AdminUser::factory()->create(['department_id' => $child->id]);
        $inactive = AdminUser::factory()->create(['department_id' => $child->id, 'active' => false]);
        $this->signIn($superior);
        $this->getJson(self::BASE.'admin/department/employees/'.$child->id)->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('can_set_leaders', true);
        $this->getJson(self::BASE.'admin/department/employees/'.$other->id)->assertForbidden();
        $this->postJson(self::BASE.'admin/department/leaders/'.$child->id, ['leader_ids' => [$inactive->id]])->assertUnprocessable();
        $this->postJson(self::BASE.'admin/department/leaders/'.$child->id, ['leader_ids' => [$superior->id]])->assertUnprocessable();
        $this->postJson(self::BASE.'admin/department/leaders/'.$child->id, ['leader_ids' => []])->assertUnprocessable();
        $this->postJson(self::BASE.'admin/department/leaders/'.$child->id, ['leader_ids' => [$employee->id]])->assertOk();
        $this->assertTrue($employee->fresh()->is_leader);
        $this->assertFalse($leader->fresh()->is_leader);
        $this->signIn($employee);
        $this->getJson(self::BASE.'admin/department/employees/'.$child->id)->assertOk()->assertJsonPath('can_set_leaders', false);
        $this->postJson(self::BASE.'admin/department/leaders/'.$child->id, ['leader_ids' => [$leader->id]])->assertForbidden();
        $this->signIn($leader);
        $this->getJson(self::BASE.'admin/department/employees/'.$child->id)->assertForbidden();
        $this->signIn();
        $this->postJson(self::BASE.'admin/department/leaders/'.$child->id, ['leader_ids' => [$leader->id, $employee->id]])->assertOk();
    }

    public function test_user_details_are_scoped_and_creation_metadata_is_server_owned(): void
    {
        $actor = $this->signIn();
        $response = $this->postJson(self::BASE.'admin/user/create', [
            'name' => 'Details user', 'email' => 'details@example.test', 'password' => 'TestingPass123!',
            'created_by_name' => 'Forged', 'created_ip' => '8.8.8.8',
        ])->assertCreated();
        $id = $response->json('data.id');
        $this->getJson(self::BASE.'admin/user/detail/'.$id)->assertOk()
            ->assertJsonPath('data.created_by_name', $actor->name)
            ->assertJsonPath('data.created_ip', '127.0.0.1')
            ->assertJsonMissingPath('data.password');
        $this->postJson(self::BASE.'admin/user/password/'.$id, [
            'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!',
        ])->assertOk();
        $this->getJson(self::BASE.'admin/user/detail/'.$id)->assertOk()->assertJsonPath('data.password_changed_ip', '127.0.0.1');
        $this->assertNotNull(AdminUser::findOrFail($id)->password_changed_at);
        $department = $this->department();
        $employee = AdminUser::factory()->create(['department_id' => $department->id]);
        $this->signIn($employee);
        $this->getJson(self::BASE.'admin/user/detail/'.$id)->assertForbidden();
        $this->getJson(self::BASE.'admin/user/detail/'.$employee->id)->assertOk();
    }

    public function test_department_can_explicitly_skip_creating_a_leader(): void
    {
        $this->signIn();
        $id = $this->postJson(self::BASE.'admin/department/create', $this->payload(null, ['create_leader' => false]))->assertCreated()->json('data.id');
        $this->assertSame(0, AdminDepartment::findOrFail($id)->leaders()->count());
        $this->postJson(self::BASE.'admin/user/create', ['name' => 'New employee', 'email' => 'newemployee@example.test', 'password' => 'TestingPass123!', 'department_id' => $id])->assertCreated();
    }

    public function test_default_departments_have_leaders_and_reseeding_preserves_passwords_and_grants(): void
    {
        foreach (['operations' => 'operate001', 'development' => 'dev0001', 'human_resources' => 'hr00001'] as $code => $username) {
            $department = AdminDepartment::where('code', $code)->sole();
            $leader = $department->leaders()->sole();
            $this->assertSame($username, $leader->username);
            $this->assertTrue(Hash::check($code === 'human_resources' ? '1234qwer' : '12345qwert', $leader->password));
            $this->assertSame(0, $department->menus()->count());
        }
        $leader->password = 'ChangedPassword123';
        $leader->save();
        $department->menus()->attach(AdminMenu::first()->id);
        $this->seed(DatabaseSeeder::class);
        $this->assertTrue(Hash::check('ChangedPassword123', $leader->fresh()->password));
        $this->assertSame(1, $department->menus()->count());
    }

    public function test_separate_permission_assignment_preserves_department_details_and_prunes_descendants(): void
    {
        $this->signIn();
        $parent = $this->department();
        $child = $this->department($parent);
        $before = $parent->fresh()->getAttributes();
        $leaders = $parent->leaders()->pluck('id')->all();
        $this->postJson(self::BASE.'admin/department/permissions/'.$parent->id, ['menu_ids' => []])->assertOk();
        $this->assertSame($before, $parent->fresh()->getAttributes());
        $this->assertSame($leaders, $parent->leaders()->pluck('id')->all());
        $this->assertSame(0, $parent->menus()->count());
        $this->assertSame(0, $child->menus()->count());
        $this->assertDatabaseHas('admin_logs', ['action' => 'admin.department.permissions']);
        $board = AdminDepartment::where('code', 'board')->sole();
        $this->postJson(self::BASE.'admin/department/permissions/'.$board->id, ['menu_ids' => []])->assertUnprocessable();
        $this->signIn($parent->leaders()->first());
        $this->postJson(self::BASE.'admin/department/permissions/'.$child->id, ['menu_ids' => []])->assertForbidden();
    }

    public function test_board_member_without_super_flag_has_all_permissions_and_root_is_protected(): void
    {
        $board = AdminDepartment::where('code', 'board')->sole();
        $member = AdminUser::factory()->create(['department_id' => $board->id]);
        $this->signIn($member);
        $this->getJson(self::BASE.'auth/userinfo')->assertOk()->assertJsonPath('permissions', ['*']);
        $this->postJson(self::BASE.'admin/department/delete/'.$board->id)->assertUnprocessable();
        $this->postJson(self::BASE.'admin/department/update/'.$board->id, $this->payload(null, ['leader_ids' => [$member->id], 'active' => false]))->assertUnprocessable();
        $this->assertTrue($board->fresh()->active);
        $super = AdminUser::where('username', 'admin')->sole();
        $this->postJson(self::BASE.'admin/user/delete/'.$super->id)->assertForbidden();
        $this->postJson(self::BASE.'admin/user/update/'.$super->id, $this->employeePayload($super, ['department_id' => $this->department()->id]))->assertForbidden();
    }

    public function test_department_creation_requires_one_or_two_leaders_and_is_atomic(): void
    {
        $this->signIn();
        $before = AdminDepartment::count();
        $this->postJson(self::BASE.'admin/department/create', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('leader_ids');
        $staff = AdminUser::factory()->count(3)->create();
        $this->postJson(self::BASE.'admin/department/create', $this->payload(null, ['leader_ids' => $staff->pluck('id')->all()]))->assertUnprocessable();
        $this->assertSame($before, AdminDepartment::count());

        $id = $this->postJson(self::BASE.'admin/department/create', $this->payload(null, ['leader_ids' => $staff->take(2)->pluck('id')->all()]))
            ->assertCreated()->json('data.id');
        $this->assertSame(2, AdminDepartment::findOrFail($id)->leaders()->count());
    }

    public function test_department_and_new_leader_are_created_together_without_exposing_password(): void
    {
        $this->signIn();
        $data = $this->payload(null, ['leader' => ['name' => 'Team leader', 'username' => 'teamleader', 'email' => 'teamlead@example.test', 'password' => 'LeaderPassword123!', 'locale' => 'en']]);
        unset($data['leader_ids']);
        $id = $this->postJson(self::BASE.'admin/department/create', $data)->assertCreated()->assertJsonMissingPath('data.leader')->json('data.id');
        $leader = AdminUser::where('username', 'teamleader')->sole();
        $this->assertSame($id, $leader->department_id);
        $this->assertTrue($leader->is_leader);
        $this->assertTrue(Hash::check('LeaderPassword123!', $leader->password));
        $this->assertStringNotContainsString('LeaderPassword123!', AdminLog::all()->toJson());
    }

    public function test_leader_can_create_direct_child_with_held_permissions(): void
    {
        $parent = $this->department();
        $actor = $this->signIn($parent->leaders()->sole());
        $staff = AdminUser::factory()->create(['department_id' => $parent->id]);
        $menuId = AdminMenu::where('permission', 'admin.user.list')->sole()->id;
        $id = $this->postJson(self::BASE.'admin/department/create', $this->payload(null, [
            'parent_id' => $parent->id, 'leader_ids' => [$staff->id], 'menu_ids' => [$menuId],
        ]))->assertCreated()->json('data.id');
        $this->assertSame($parent->id, AdminDepartment::findOrFail($id)->parent_id);
        $this->assertSame(['admin.user.list'], $staff->fresh()->permissions());
        $this->assertTrue($actor->fresh()->is_leader);
    }

    public function test_non_leader_with_button_permissions_cannot_manage_staff_or_delegate(): void
    {
        $department = $this->department();
        $employee = AdminUser::factory()->create(['department_id' => $department->id]);
        $this->signIn($employee);
        $target = AdminUser::factory()->create(['department_id' => $department->id]);
        $this->postJson(self::BASE.'admin/department/create', $this->payload(null, ['parent_id' => $department->id, 'leader_ids' => [$target->id]]))->assertForbidden();
        $this->postJson(self::BASE.'admin/user/update/'.$target->id, $this->employeePayload($target))->assertForbidden();
        $this->postJson(self::BASE.'admin/user/delete/'.$target->id)->assertForbidden();
    }

    public function test_leader_cannot_cross_branches_promote_to_board_or_edit_own_grants(): void
    {
        $department = $this->department();
        $sibling = $this->department();
        $leader = $this->signIn($department->leaders()->sole());
        $target = AdminUser::factory()->create(['department_id' => $department->id]);
        $outsider = $sibling->leaders()->sole();
        $this->postJson(self::BASE.'admin/department/update/'.$department->id, $this->payload($department))->assertForbidden();
        $this->postJson(self::BASE.'admin/department/update/'.$sibling->id, $this->payload($sibling))->assertForbidden();
        $this->postJson(self::BASE.'admin/department/create', $this->payload(null, ['parent_id' => $sibling->id, 'leader_ids' => [$target->id]]))->assertForbidden();
        $this->postJson(self::BASE.'admin/user/update/'.$target->id, $this->employeePayload($target, ['department_id' => AdminDepartment::where('code', 'board')->sole()->id]))->assertForbidden();
        $this->postJson(self::BASE.'admin/user/password/'.$outsider->id, ['password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'])->assertForbidden();
        $this->assertNotContains($outsider->id, array_column($this->getJson(self::BASE.'admin/user/list')->assertOk()->json('data'), 'id'));
        $this->assertNotContains($sibling->id, array_column($this->getJson(self::BASE.'admin/department/list')->assertOk()->json('data'), 'id'));
        $this->assertSame($department->id, $leader->fresh()->department_id);
    }

    public function test_unowned_grants_and_global_configuration_changes_are_rejected(): void
    {
        $allowed = AdminMenu::where('permission', 'like', 'admin.department.%')->pluck('id')->all();
        $parent = $this->department(permissions: $allowed);
        $this->signIn($parent->leaders()->sole());
        $staff = AdminUser::factory()->create(['department_id' => $parent->id]);
        $forbidden = AdminMenu::where('permission', 'admin.user.delete')->sole()->id;
        $this->postJson(self::BASE.'admin/department/create', $this->payload(null, ['parent_id' => $parent->id, 'leader_ids' => [$staff->id], 'menu_ids' => [$forbidden]]))
            ->assertUnprocessable()->assertJsonValidationErrors('menu_ids');
        $this->assertFalse($staff->fresh()->is_leader);
        $this->postJson(self::BASE.'roles', ['name_i18n' => ['zh_CN' => 'Escalation'], 'active' => true, 'menu_ids' => [$forbidden]])->assertNotFound();
    }

    public function test_ancestor_revocation_removes_descendant_grants_and_does_not_restore_them(): void
    {
        $parent = $this->department();
        $child = $this->department($parent);
        $grandchild = $this->department($child);
        $leader = $grandchild->leaders()->sole();
        $revoked = AdminMenu::where('permission', 'admin.user.delete')->sole()->id;
        $this->assertTrue($leader->permits('admin.user.delete'));
        $this->signIn();
        $this->postJson(self::BASE.'admin/department/update/'.$parent->id, $this->payload($parent, ['menu_ids' => array_values(array_diff(AdminMenu::pluck('id')->all(), [$revoked]))]))->assertOk();
        $this->assertFalse($leader->fresh()->permits('admin.user.delete'));
        $this->assertDatabaseMissing('admin_department_menu', ['department_id' => $grandchild->id, 'menu_id' => $revoked]);
        $this->postJson(self::BASE.'admin/department/update/'.$parent->id, $this->payload($parent, ['menu_ids' => AdminMenu::pluck('id')->all()]))->assertOk();
        $this->assertFalse($leader->fresh()->permits('admin.user.delete'));
    }

    public function test_last_leader_cannot_be_removed_disabled_demoted_or_transferred(): void
    {
        $department = $this->department();
        $leader = $department->leaders()->sole();
        $this->signIn();
        $this->postJson(self::BASE.'admin/user/delete/'.$leader->id)->assertUnprocessable();
        foreach ([
            ['active' => false],
            ['is_leader' => false],
            ['department_id' => AdminDepartment::where('code', 'board')->sole()->id],
        ] as $changes) {
            $this->postJson(self::BASE.'admin/user/update/'.$leader->id, $this->employeePayload($leader, $changes))->assertUnprocessable();
        }
        $this->assertTrue($leader->fresh()->active);
        $this->assertSame($department->id, $leader->fresh()->department_id);
        $this->postJson(self::BASE.'admin/department/create', $this->payload(null, ['leader_ids' => [$leader->id]]))->assertUnprocessable();
        $this->assertSame(5, AdminDepartment::count());
    }

    public function test_leaders_are_assigned_only_in_department_and_two_leader_limit_is_enforced(): void
    {
        $department = $this->department();
        $leader = $department->leaders()->sole();
        $replacement = AdminUser::factory()->create(['department_id' => $department->id]);
        $third = AdminUser::factory()->create(['department_id' => $department->id]);
        $this->signIn();
        $this->postJson(self::BASE.'admin/user/update/'.$replacement->id, $this->employeePayload($replacement, ['is_leader' => true]))->assertUnprocessable();
        $this->postJson(self::BASE.'admin/department/update/'.$department->id, $this->payload($department, ['leader_ids' => [$leader->id, $replacement->id]]))->assertOk();
        $this->postJson(self::BASE.'admin/department/update/'.$department->id, $this->payload($department, ['leader_ids' => [$leader->id, $replacement->id, $third->id]]))->assertUnprocessable();
        $this->postJson(self::BASE.'admin/department/update/'.$department->id, $this->payload($department, ['leader_ids' => [$replacement->id]]))->assertOk();
        $this->postJson(self::BASE.'admin/user/status/'.$leader->id, ['active' => false])->assertOk();
        $this->postJson(self::BASE.'admin/user/delete/'.$leader->id)->assertOk();
        $this->assertSame(1, $department->leaders()->count());
    }

    public function test_employees_inherit_department_permissions_without_roles_but_cannot_manage_staff(): void
    {
        $ids = AdminMenu::whereIn('permission', ['admin.user.list', 'admin.user.create'])->pluck('id')->all();
        $department = $this->department(null, $ids);
        $employee = AdminUser::factory()->create(['department_id' => $department->id]);
        $this->assertTrue($employee->permits('admin.user.list'));
        $this->assertFalse($employee->permits('admin.user.delete'));
        $this->signIn($employee);
        $this->getJson(self::BASE.'admin/user/list')->assertOk();
        $this->postJson(self::BASE.'admin/user/create', [
            'name' => 'Denied', 'email' => 'denied@example.test', 'password' => 'ValidPassword123',
            'locale' => 'en', 'active' => true, 'department_id' => $department->id,
        ])->assertForbidden();
        $department->menus()->detach();
        $this->assertSame([], $employee->fresh()->permissions());
    }

    public function test_status_requires_security_password_and_disables_descendant_permissions(): void
    {
        $parent = $this->department();
        $child = $this->department($parent);
        $leader = $child->leaders()->sole();
        $admin = AdminUser::where('username', 'admin')->sole();
        $admin->forceFill(['security_password' => 'DepartmentSafe123'])->save();
        $this->signIn($admin);
        $url = self::BASE.'admin/department/status/'.$parent->id;

        $this->postJson($url, ['active' => false])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->postJson($url, ['active' => false, 'security_password' => 'WrongSafe123'])->assertUnprocessable()->assertJsonValidationErrors('security_password');
        $this->assertTrue($parent->fresh()->active);
        $this->postJson($url, ['active' => false, 'security_password' => 'DepartmentSafe123'])->assertOk();

        $this->assertFalse($parent->fresh()->active);
        $this->assertFalse($leader->fresh()->permits('admin.user.create'));
        $board = AdminDepartment::where('code', 'board')->sole();
        $this->postJson(self::BASE.'admin/department/status/'.$board->id, ['active' => false, 'security_password' => 'DepartmentSafe123'])->assertUnprocessable();
        $this->assertTrue($board->fresh()->active);
    }

    public function test_disabling_parent_immediately_disables_descendant_permissions(): void
    {
        $parent = $this->department();
        $child = $this->department($parent);
        $leader = $child->leaders()->sole();
        $this->assertTrue($leader->permits('admin.user.create'));
        $this->signIn();
        $this->postJson(self::BASE.'admin/department/update/'.$parent->id, $this->payload($parent, ['active' => false]))->assertOk();
        $this->assertFalse($leader->fresh()->permits('admin.user.create'));
    }
}

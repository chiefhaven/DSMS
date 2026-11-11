<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        foreach (['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000700_seed_settings_permissions.php'))->up();
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->dateTime('attendance_time_start')->nullable();
            $table->dateTime('attendance_time_stop')->nullable();
        });
        Schema::create('invoice_settings', fn (Blueprint $table) => $table->id());
        Schema::create('districts', fn (Blueprint $table) => $table->id());
        DB::table('settings')->insert([
            'id' => 1,
            'attendance_time_start' => '2026-01-01 08:00:00',
            'attendance_time_stop' => '2026-01-01 17:00:00',
        ]);
        DB::table('invoice_settings')->insert(['id' => 1]);
    }

    public function test_read_only_settings_page_disables_all_edit_sections(): void
    {
        $role = Role::create(['name' => 'settings-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view settings');
        $this->authenticate('settings-reader');

        $response = $this->get('/settings')->assertOk();
        $this->assertSame(3, substr_count($response->getContent(), '<fieldset  disabled '));
        $response->assertDontSee('id="invoicesettings-update"', false);
        $this->putJson('/settings', [])->assertForbidden();
        $this->postJson('/settings-update', [])->assertForbidden();
        $this->postJson('/attendance-time-update', [])->assertForbidden();
        $this->putJson('/invoice-settings', [])->assertForbidden();
        $this->postJson('/invoicesettings-update', [])->assertForbidden();
    }

    public function test_edit_permissions_are_independent(): void
    {
        $role = Role::create(['name' => 'school-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit school settings');
        $this->authenticate('school-editor');

        $this->putJson('/settings', [])->assertUnprocessable();
        $this->postJson('/attendance-time-update', [])->assertForbidden();
        $this->putJson('/invoice-settings', [])->assertForbidden();
    }

    public function test_migration_preserves_existing_administration_grants(): void
    {
        $role = Role::create(['name' => 'settings-manager', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        $user = new User();
        $user->id = 'direct-manager';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage administration')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        (require base_path('database/migrations/2026_11_12_000700_seed_settings_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000700_seed_settings_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('edit school settings'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('edit invoice settings')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(string $role): void
    {
        $user = new User(['name' => 'Settings test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([Role::findByName($role, 'web')]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}

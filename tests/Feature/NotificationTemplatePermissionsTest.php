<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationTemplatePermissionsTest extends TestCase
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
        (require base_path('database/migrations/2026_11_12_001400_seed_notification_template_permissions.php'))->up();
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('body');
            $table->string('available_tags')->nullable();
            $table->timestamps();
        });
        foreach (['New-registration', 'Enrollment', 'Balance', 'Attendance', 'Payment', 'Carassignment'] as $type) {
            DB::table('notification_templates')->insert(['type' => $type, 'body' => 'Original '.$type]);
        }
    }

    public function test_reader_can_view_templates_but_cannot_edit_them(): void
    {
        $role = Role::create(['name' => 'template-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view notification templates');
        $this->authenticate($role);

        $this->get('/sms-templates')->assertOk()
            ->assertSee('Original New-registration')
            ->assertDontSee('data-bs-target="#New-registration"', false)
            ->assertDontSee('action="'.url('/update-notification-templates/1').'"', false);
        $this->postJson('/update-notification-templates/1', ['body' => 'Changed'])->assertForbidden();
        $this->assertDatabaseHas('notification_templates', ['id' => 1, 'body' => 'Original New-registration']);
        $this->assertStringContainsString(url('/sms-templates'), view('layouts.partials.main_nav')->render());
    }

    public function test_edit_permission_is_separate_from_viewing(): void
    {
        $role = Role::create(['name' => 'template-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit notification templates');
        $this->authenticate($role);

        $this->get('/sms-templates')->assertForbidden();
        $this->post('/update-notification-templates/1', ['body' => 'Changed'])->assertRedirect();
        $this->assertDatabaseHas('notification_templates', ['id' => 1, 'body' => 'Changed']);

        $role->givePermissionTo('view notification templates');
        $this->authenticate($role);
        $this->get('/sms-templates')->assertOk()
            ->assertSee('data-bs-target="#New-registration"', false)
            ->assertSee('action="'.url('/update-notification-templates/1').'"', false);
    }

    public function test_unprivileged_user_cannot_access_or_change_templates(): void
    {
        $this->authenticate(Role::findByName('student', 'web'));
        $this->get('/sms-templates')->assertForbidden();
        $this->postJson('/update-notification-templates/1', ['body' => 'Changed'])->assertForbidden();
        $this->assertDatabaseHas('notification_templates', ['id' => 1, 'body' => 'Original New-registration']);
        $this->assertStringNotContainsString(url('/sms-templates'), view('layouts.partials.main_nav')->render());
    }

    public function test_migration_preserves_administration_grants_idempotently(): void
    {
        $role = Role::create(['name' => 'template-manager', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        $user = new User();
        $user->id = 'direct-manager';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage administration', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        (require base_path('database/migrations/2026_11_12_001400_seed_notification_template_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001400_seed_notification_template_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('view notification templates'));
        $this->assertTrue($role->fresh()->hasPermissionTo('edit notification templates'));
        $this->assertFalse(Role::findByName('student', 'web')->hasPermissionTo('view notification templates'));
        foreach (['view notification templates', 'edit notification templates'] as $name) {
            $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
                'permission_id' => Permission::findByName($name, 'web')->id,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->id,
            ]);
        }
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Template test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}

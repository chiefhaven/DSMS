<?php

namespace Tests\Feature;

use App\Jobs\SendAnnouncementSMS;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnnouncementPermissionsTest extends TestCase
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
        (require base_path('database/migrations/2026_11_12_000800_seed_announcement_permissions.php'))->up();
    }

    public function test_viewing_an_announcement_does_not_authorize_sending(): void
    {
        $role = Role::create(['name' => 'announcement-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view announcements');
        $this->authenticate('announcement-reader');

        $this->get('/send-announcement')->assertOk()
            ->assertDontSee(':disabled="state.isSubmitButtonDisabled"', false)
            ->assertDontSee('id="announcement-modal"', false);
        $this->postJson('/api/sendAnnouncement', ['group' => 'All students', 'body' => 'Hello'])->assertForbidden();
    }

    public function test_unassigned_instructor_never_broadcasts_to_all_students(): void
    {
        $role = Role::create(['name' => 'announcer', 'guard_name' => 'web']);
        $role->givePermissionTo('send announcements');
        $this->authenticate('announcer');
        Queue::fake();
        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        Schema::create('fleets', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('instructor_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('classrooms', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        Schema::create('classroom_instructor', function (Blueprint $table) {
            $table->string('classroom_id');
            $table->string('instructor_id');
        });
        DB::table('instructors')->insert(['id' => 'unassigned']);

        $this->postJson('/api/sendAnnouncement', [
            'group' => 'All students', 'body' => 'Hello', 'instructor' => 'unassigned',
        ])->assertNotFound()->assertJson(['message' => 'No students found']);
        Queue::assertNotPushed(SendAnnouncementSMS::class);
    }

    public function test_migration_preserves_qualified_existing_grants(): void
    {
        $role = Role::create(['name' => 'system-only', 'guard_name' => 'web']);
        $role->givePermissionTo('manage system');
        $user = new User();
        $user->id = 'direct-system';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage system')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        (require base_path('database/migrations/2026_11_12_000800_seed_announcement_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000800_seed_announcement_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('send announcements'));
        $this->assertFalse($role->fresh()->hasPermissionTo('view announcements'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('send announcements')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(string $role): void
    {
        $user = new User(['name' => 'Announcement test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([Role::findByName($role, 'web')]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}

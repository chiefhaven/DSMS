<?php

namespace Tests\Feature;

use App\Http\Controllers\InstructorPaymentController;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
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

        $permissionMigration = implode(DIRECTORY_SEPARATOR, [
            'vendor', 'spatie', 'laravel-permission', 'database', 'migrations',
            'create_permission_tables.php.stub',
        ]);
        (require base_path($permissionMigration))->up();

        foreach (['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }

        $accessMigration = implode(DIRECTORY_SEPARATOR, [
            'database', 'migrations', '2026_11_11_120000_seed_access_permissions.php',
        ]);
        (require base_path($accessMigration))->up();
        (require base_path('database/migrations/2026_11_12_000000_seed_schedule_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000100_seed_role_action_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000200_seed_student_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000300_seed_course_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000400_seed_lesson_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000500_seed_classroom_fleet_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000600_seed_expense_type_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000700_seed_settings_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000800_seed_announcement_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000900_seed_expense_payment_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001000_seed_expense_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001100_seed_invoice_permissions.php'))->up();
        Role::create(['name' => 'hrm-clerk', 'guard_name' => 'web'])->givePermissionTo('manage administration');
        Role::create(['name' => 'system-operator', 'guard_name' => 'web'])->givePermissionTo('manage system');
    }

    /** @dataProvider menuAccess */
    public function test_sidebar_groups_links_for_authorized_users(?string $role, bool $hrmVisible, bool $rolesVisible): void
    {
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect($role === null ? [] : [Role::findByName($role, 'web')]));
        $this->actingAs($user);

        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML(
            view('layouts.partials.main_nav')->render(),
            LIBXML_NOERROR | LIBXML_NOWARNING
        ));
        $xpath = new DOMXPath($document);
        $hrm = $xpath->query("//ul[contains(concat(' ', normalize-space(@class), ' '), ' Sidebar ')]/li[a/span[normalize-space(.)='HRM']]");
        $bonusLinks = $xpath->query("//a[contains(@href, '/instructor-payments')]");

        $this->assertSame($hrmVisible ? 1 : 0, $hrm->length);
        $this->assertSame($hrmVisible ? 1 : 0, $bonusLinks->length);

        $roleLinks = $xpath->query("//a[span[normalize-space(.)='Roles & permissions']]");
        $this->assertSame($rolesVisible ? 1 : 0, $roleLinks->length);

        if ($rolesVisible) {
            $users = $xpath->query("//ul[contains(concat(' ', normalize-space(@class), ' '), ' Sidebar ')]/li[a/span[normalize-space(.)='Users']]");
            $this->assertSame(1, $users->length);
            $submenuRoles = $xpath->query(
                "./ul[contains(concat(' ', normalize-space(@class), ' '), ' nav-main-submenu ')]/li/a[span[normalize-space(.)='Roles & permissions']]",
                $users->item(0)
            );
            $this->assertSame(1, $submenuRoles->length);
            $rolesLink = $submenuRoles->item(0);
            $this->assertInstanceOf(DOMElement::class, $rolesLink);
            $this->assertSame(route('roles.index'), $rolesLink->getAttribute('href'));
        }

        if (!$hrmVisible) {
            return;
        }

        $toggle = $xpath->query(
            "./a[contains(concat(' ', normalize-space(@class), ' '), ' nav-main-link-submenu ') and @data-toggle='submenu' and @aria-haspopup='true' and @aria-expanded='false']",
            $hrm->item(0)
        );
        $submenuLinks = $xpath->query(
            "./ul[contains(concat(' ', normalize-space(@class), ' '), ' nav-main-submenu ')]/li/a[span[normalize-space(.)='Bonus Payments']]",
            $hrm->item(0)
        );

        $this->assertSame(1, $toggle->length);
        $this->assertSame(1, $submenuLinks->length);
        $link = $submenuLinks->item(0);
        $this->assertInstanceOf(DOMElement::class, $link);
        $this->assertSame(url('/instructor-payments'), $link->getAttribute('href'));
    }

    public static function menuAccess(): array
    {
        return [
            'administrator' => ['admin', true, false],
            'super administrator' => ['superAdmin', true, true],
            'legacy super administrator' => ['Super-Admin', true, true],
            'custom administration role' => ['hrm-clerk', false, false],
            'system-only role' => ['system-operator', false, false],
            'finance administrator' => ['financeAdmin', false, false],
            'instructor' => ['instructor', false, false],
            'student' => ['student', false, false],
            'roleless user' => [null, false, false],
        ];
    }

    public function test_navigation_uses_roles_but_actions_require_permissions(): void
    {
        $role = Role::findByName('admin', 'web');
        $role->revokePermissionTo('manage administration');
        $role->revokePermissionTo('create students');
        $role->revokePermissionTo('create expenses');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $html = view('layouts.partials.main_nav')->render();

        $this->assertStringContainsString('Bonus Payments', $html);
        $this->assertStringContainsString('All students', $html);
        $this->assertStringNotContainsString('Add student', $html);
        $this->assertStringNotContainsString('Add expense', $html);
    }

    public function test_custom_role_with_schedule_access_sees_the_schedule_link(): void
    {
        $role = Role::create(['name' => 'schedule-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view schedules');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $html = view('layouts.partials.main_nav')->render();

        $this->assertStringContainsString(route('schedules.index'), $html);
    }

    public function test_custom_role_with_student_access_sees_the_student_link(): void
    {
        $role = Role::create(['name' => 'student-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view students');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $html = view('layouts.partials.main_nav')->render();

        $this->assertStringContainsString('All students', $html);
        $this->assertStringNotContainsString('Add student', $html);
    }

    public function test_custom_role_with_course_access_sees_only_the_course_school_link(): void
    {
        $role = Role::create(['name' => 'course-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view courses');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $html = view('layouts.partials.main_nav')->render();

        $this->assertStringContainsString('Courses', $html);
        $this->assertStringNotContainsString('Classrooms', $html);
    }

    public function test_custom_role_with_lesson_access_sees_the_lesson_link(): void
    {
        $role = Role::create(['name' => 'lesson-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view lessons');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $html = view('layouts.partials.main_nav')->render();

        $this->assertStringContainsString('Lessons', $html);
        $this->assertStringNotContainsString('Classrooms', $html);
    }

    public function test_custom_role_sees_only_its_classroom_and_fleet_links(): void
    {
        $role = Role::create(['name' => 'resource-reader', 'guard_name' => 'web']);
        $role->givePermissionTo(['view classrooms', 'view fleet']);
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $html = view('layouts.partials.main_nav')->render();

        $this->assertStringContainsString('Classrooms', $html);
        $this->assertStringContainsString(url('/fleet'), $html);
        $this->assertStringNotContainsString('Track fleet', $html);
        $this->assertStringNotContainsString('Courses', $html);
    }

    public function test_custom_expense_type_reader_sees_only_its_expense_link(): void
    {
        $role = Role::create(['name' => 'expense-type-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view expense types');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $html = view('layouts.partials.main_nav')->render();

        $this->assertStringContainsString('Expense types', $html);
        $this->assertStringNotContainsString('All expenses', $html);
    }

    public function test_custom_role_with_settings_access_sees_the_settings_link(): void
    {
        $role = Role::create(['name' => 'settings-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view settings');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $this->assertStringContainsString(url('/settings'), view('layouts.partials.main_nav')->render());
    }

    public function test_announcement_navigation_requires_role_access_and_send_permission(): void
    {
        $role = Role::create(['name' => 'announcement-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view announcements');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $this->assertStringNotContainsString('Make Announcement', view('layouts.partials.main_nav')->render());
        $role->givePermissionTo('send announcements');
        $user->setRelation('roles', collect([$role->fresh()]));
        $this->assertStringContainsString('Make Announcement', view('layouts.partials.main_nav')->render());
    }

    public function test_custom_role_with_role_access_sees_the_roles_link(): void
    {
        $role = Role::create(['name' => 'role-viewer', 'guard_name' => 'web']);
        $role->givePermissionTo('view roles');
        $user = new User(['name' => 'Navigation test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);

        $html = view('layouts.partials.main_nav')->render();

        $this->assertStringContainsString(route('roles.index'), $html);
    }

    public function test_bonus_payment_url_resolves_to_the_existing_page(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/instructor-payments', 'GET'));

        $this->assertSame(InstructorPaymentController::class.'@index', $route->getActionName());
        $this->assertContains('auth', $route->middleware());
    }
}

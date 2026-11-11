<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorController;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AdministratorAccountTest extends InstructorAccountTest
{
    protected string $resource = 'administrators';
    protected string $instructor = '123';

    public function test_show_loads_the_requested_profile_and_linked_user_roles(): void
    {
        $administrator = Administrator::query()->firstOrFail();
        $view = app(AdministratorController::class)->show($administrator);
        $profile = $view->getData()['administrator'];

        $this->assertSame('administrators.viewadministrator', $view->name());
        $this->assertTrue($profile->is($administrator));
        $this->assertSame((string) $administrator->id, $profile->User->administrator_id);
        $this->assertTrue($profile->User->relationLoaded('roles'));
    }

    public function test_show_supports_profiles_without_linked_accounts(): void
    {
        DB::table('administrators')->insert(['id' => '456']);
        $view = app(AdministratorController::class)->show(Administrator::findOrFail('456'));

        $this->assertSame(456, $view->getData()['administrator']->id);
        $this->assertNull($view->getData()['administrator']->User);
    }

    public function test_legacy_and_own_profile_resolve_the_signed_in_administrator(): void
    {
        $user = User::whereNotNull('administrator_id')->firstOrFail();
        $this->actingAs($user);

        foreach ([
            app(AdministratorController::class)->show(new Administrator),
            app(AdministratorController::class)->showProfile(),
        ] as $view) {
            $this->assertSame($user->administrator_id, (string) $view->getData()['administrator']->id);
        }
    }

    public function test_own_profile_without_an_administrator_is_not_found(): void
    {
        $this->actingAs(User::whereNull('administrator_id')->firstOrFail());
        $this->expectException(ModelNotFoundException::class);
        app(AdministratorController::class)->showProfile();
    }
}

<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(fn ($user) => $user->hasAnyRole(['superAdmin', 'Super-Admin']) ? true : null);

        Gate::define('view-dashboard', fn ($user) =>
            $user->can('access management')
            || ($user->hasAnyRole(['instructor', 'student']) && $user->can('access portal'))
        );
    }
}

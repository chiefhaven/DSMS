<?php

namespace App\Providers;
use Illuminate\Support\Facades\Schema;
use App\Models\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;


use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        date_default_timezone_set('Africa/Blantyre');
    }
}

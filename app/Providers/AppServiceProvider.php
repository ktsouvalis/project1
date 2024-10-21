<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::loadKeysFrom(storage_path());
        Passport::tokensCan([
            'manage-posts' => 'Manage Posts',
        ]);
        Passport::setDefaultScope([
            'manage-posts',
        ]);
    }
}

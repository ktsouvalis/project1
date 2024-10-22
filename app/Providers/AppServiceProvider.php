<?php

namespace App\Providers;

use App\Models\Post;
use App\Policies\PostPolicy;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

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
            'manage-resources' => 'Manage Resources',
        ]);
        Passport::setDefaultScope([
            'manage-resources',
        ]);
        Gate::policy(Post::class, PostPolicy::class);
    }
}

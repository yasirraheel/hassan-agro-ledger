<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        Blade::if('usercan', function ($permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });

        Blade::if('usercanany', function ($permissions) {
            return auth()->check() && auth()->user()->hasAnyPermission($permissions);
        });

        if (file_exists(base_path('storage/installed'))) {
            view()->composer('*', function ($view) {
                $user = auth()->user();

                $key = $user?->business_id ?? 'admin';
                $theme = get_option("theme_setting_" . $key);

                $view->with('theme', $theme);
            });
        }
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\UserGuides;
use App\Observers\UserGuidesObserver;

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
        /*
        |--------------------------------------------------------------------------
        | Blade Directive
        |--------------------------------------------------------------------------
        */
        Blade::directive('generate_tags', function ($expression) {
            return "<?php 
                echo generate_tags($expression);
            ?>";
        });

        /*
        |--------------------------------------------------------------------------
        | Pagination Bootstrap
        |--------------------------------------------------------------------------
        */
        Paginator::useBootstrapFive();

        /*
        |--------------------------------------------------------------------------
        | Observer User Guides Actions
        |--------------------------------------------------------------------------
        */
        UserGuides::observe(UserGuidesObserver::class);
    }
}

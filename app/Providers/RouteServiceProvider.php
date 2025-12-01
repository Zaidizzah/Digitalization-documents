<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Traits\ApiResponse;
use Illuminate\Http\Response;

class RouteServiceProvider extends ServiceProvider
{
    use ApiResponse;

    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(env('DEFAULT_API_RATE_LIMIT', 60))->by($request->user()?->id ?: $request->ip())
                ->response(fn(Request $request, array $headers) => $this->limit_excedeed_response($request, $headers, env('DEFAULT_API_RATE_LIMIT', 60)));
        });

        RateLimiter::for('generous', function (Request $request) {
            return Limit::perMinute(env('DEFAULT_GENEROUS_RATE_LIMIT', 300))->by($request->user()?->id ?: $request->ip())
                ->response(fn(Request $request, array $headers) => $this->limit_excedeed_response($request, $headers, env('DEFAULT_GENEROUS_RATE_LIMIT', 300)));
        });

        RateLimiter::for('moderate', function (Request $request) {
            return Limit::perMinute(env('DEFAULT_MODERATE_RATE_LIMIT', 60))->by($request->user()?->id ?: $request->ip())
                ->response(fn(Request $request, array $headers) => $this->limit_excedeed_response($request, $headers, env('DEFAULT_MODERATE_RATE_LIMIT', 60)));
        });

        RateLimiter::for('strict', function (Request $request) {
            return Limit::perMinutes(5, env('DEFAULT_STRICT_RATE_LIMIT', 10))->by($request->user()?->id ?: $request->ip())
                ->response(fn(Request $request, array $headers) => $this->limit_excedeed_response($request, $headers, env('DEFAULT_STRICT_RATE_LIMIT', 5)));
        });

        RateLimiter::for('very_strict', function (Request $request) {
            return Limit::perMinutes(15, env('DEFAULT_VERY_STRICT_RATE_LIMIT', 5))->by($request->user()?->id ?: $request->ip())
                ->response(fn(Request $request, array $headers) => $this->limit_excedeed_response($request, $headers, env('DEFAULT_VERY_STRICT_RATE_LIMIT', 15)));
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}

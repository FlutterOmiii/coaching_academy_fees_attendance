<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();

        /*
         * @ability('fees.view') ... @endability
         *
         * Passes when the signed-in admin holds ANY of the given abilities.
         * Views use this to hide actions a role cannot perform; the routes
         * enforce the same abilities server-side, so this is presentation
         * only — never the actual guard.
         */
        Blade::if('ability', function (string ...$abilities) {
            $admin = auth('admin')->user();

            if (! $admin) {
                return false;
            }

            foreach ($abilities as $ability) {
                if ($admin->hasAbility($ability)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Throttle for the website admission feed.
     *
     * Generous enough for a real intake and its retries, tight enough that a
     * leaked token cannot be used to hammer the endpoint. The 429 uses the same
     * envelope as every other API response so the website parses one shape.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('website-admissions', fn (Request $request) => Limit::perMinute(60)
            ->by($request->ip())
            ->response(fn () => response()->json([
                'success' => false,
                'message' => 'Too many admission requests. Please retry shortly.',
            ], 429)));
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
}

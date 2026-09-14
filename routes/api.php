<?php

use App\Http\Controllers\Api\V1\WebsiteAdmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
| Server-to-server endpoints only. Nothing here is reachable from a browser
| session: every route carries its own credential check and the group is
| stateless, so no CSRF token or admin login is involved.
|
| Routes are versioned so the public website can be upgraded independently of
| the CRM.
*/
Route::prefix('v1/website')
    ->name('api.v1.website.')
    ->middleware(['website.api', 'throttle:website-admissions'])
    ->group(function () {
        Route::post('admissions', [WebsiteAdmissionController::class, 'store'])
            ->name('admissions.store');
    });

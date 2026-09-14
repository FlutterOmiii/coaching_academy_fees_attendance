<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-to-server authentication for the public website integration.
 *
 * The website's backend sends a shared secret as a bearer token; the token
 * lives only in each side's environment file and is never handed to a browser.
 * Comparison is constant time so a wrong token leaks nothing through timing,
 * and the token itself is never written to the log.
 */
class VerifyWebsiteApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.website_admission.token');
        $presented = (string) $request->bearerToken();

        // An unset token must never mean "everything is allowed".
        if ($expected === '') {
            Log::error('Website admission API called but WEBSITE_ADMISSION_API_TOKEN is not configured.');

            return $this->unauthorised();
        }

        if ($presented === '' || ! hash_equals($expected, $presented)) {
            Log::warning('Website admission API rejected an unauthenticated request.', [
                'ip' => $request->ip(),
            ]);

            return $this->unauthorised();
        }

        return $next($request);
    }

    private function unauthorised(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Invalid or missing API credentials.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}

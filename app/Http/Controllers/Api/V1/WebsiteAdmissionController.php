<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebsiteAdmissionRequest;
use App\Http\Resources\WebsiteAdmissionResource;
use App\Services\WebsiteAdmissionImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Receives admissions pushed from the public website's backend.
 *
 * The caller is authenticated by VerifyWebsiteApiToken before anything here
 * runs. All this endpoint does is hand the validated payload to the importer
 * and shape the reply; the mapping and the transaction live in the service.
 */
class WebsiteAdmissionController extends Controller
{
    public function store(WebsiteAdmissionRequest $request, WebsiteAdmissionImporter $importer): JsonResponse
    {
        try {
            ['student' => $student, 'created' => $created] = $importer->import(
                $request->validated(),
                $request->file('photo'),
            );
        } catch (ValidationException $e) {
            // Raised when a related record the payload refers to cannot be
            // resolved — an unknown batch code, for instance.
            return response()->json([
                'success' => false,
                'message' => 'The submitted admission data is invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            // The transaction has already rolled back and any uploaded file has
            // been removed. Log enough to debug without recording the
            // applicant's personal details.
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'The admission could not be processed. Please retry.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => $created
                ? 'Admission transferred to CRM successfully.'
                : 'Admission was already transferred to CRM.',
            'data' => (new WebsiteAdmissionResource($student, $created))->resolve($request),
        ], $created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}

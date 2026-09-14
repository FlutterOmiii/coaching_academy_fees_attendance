<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Student;
use App\Support\PersonName;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Turns a validated website admission payload into a CRM student.
 *
 * Two responsibilities live here and nowhere else:
 *
 *  - Mapping. The website speaks its own field names; this class translates
 *    them into CRM columns explicitly, field by field. Nothing is mass
 *    assigned from the request, so a payload carrying extra keys — say
 *    student_code or admission_status — can never reach the database.
 *
 *  - Idempotency. website_admission_uuid is unique on the students table. A
 *    repeated delivery of the same admission returns the student that already
 *    exists instead of creating a second one, and the unique index catches the
 *    case where two retries race each other into the insert.
 */
class WebsiteAdmissionImporter
{
    public function __construct(private readonly StudentRegistrar $registrar) {}

    /**
     * @param  array<string, mixed>  $payload  output of WebsiteAdmissionRequest::validated()
     * @return array{student: Student, created: bool}
     */
    public function import(array $payload, ?UploadedFile $photo = null): array
    {
        $uuid = $payload['website_admission_uuid'];

        if ($existing = $this->findByUuid($uuid)) {
            return ['student' => $existing, 'created' => false];
        }

        $batchId = $this->resolveBatchId($payload['batch_code'] ?? null);

        try {
            $student = $this->registrar->register($this->attributes($payload), $photo, $batchId);
        } catch (QueryException $e) {
            // Two retries raced and the unique index rejected the second one.
            // The winner is the record both callers should be told about.
            if ($existing = $this->findByUuid($uuid)) {
                return ['student' => $existing, 'created' => false];
            }

            throw $e;
        }

        return ['student' => $student, 'created' => true];
    }

    /**
     * Soft-deleted students count: an admission already imported and later
     * removed in the panel must not come back through a retry.
     */
    private function findByUuid(string $uuid): ?Student
    {
        return Student::withTrashed()->where('website_admission_uuid', $uuid)->first();
    }

    /**
     * Resolve the website's readable batch code to a CRM batch id.
     *
     * Batches are master data, so an unrecognised code is reported back as a
     * validation error; the API never creates one on the fly.
     */
    private function resolveBatchId(?string $code): ?int
    {
        if (blank($code)) {
            return null;
        }

        $batch = Batch::where('code', $code)->first();

        if (! $batch) {
            throw ValidationException::withMessages([
                'batch_code' => "No batch matches the code \"{$code}\".",
            ]);
        }

        return (int) $batch->id;
    }

    /**
     * Explicit website field -> CRM column mapping.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function attributes(array $payload): array
    {
        $name = PersonName::split((string) $payload['full_name']);

        $attributes = [
            'first_name' => $name['first_name'],
            'last_name' => $name['last_name'],
            'date_of_birth' => $payload['date_of_birth'],
            'blood_group' => $payload['blood_group'] ?? null,
            'school_name' => $payload['school_name'] ?? null,

            'guardian_name' => $payload['guardian_name'],
            'guardian_phone' => $payload['guardian_phone'],
            'guardian_email' => $payload['guardian_email'] ?? null,
            'guardian_relation' => $payload['guardian_relation'] ?? null,

            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'city' => $payload['city'] ?? null,
            'state' => $payload['state'] ?? null,
            'pincode' => $payload['pincode'] ?? null,

            'batting_style' => $payload['batting_style'] ?? null,
            'bowling_style' => $payload['bowling_style'] ?? null,

            'admission_date' => $payload['admission_date'] ?? now()->toDateString(),
            // A website admission is a request, not an approval: it lands in
            // the same pending state the admin panel reviews.
            'admission_status' => 'pending',
            'status' => 'active',
            'medical_notes' => $payload['medical_notes'] ?? null,
            'notes' => $this->notes($payload),

            'website_admission_uuid' => $payload['website_admission_uuid'],
            'admission_source' => Student::SOURCE_WEBSITE,
            'source_reference' => $payload['source_reference'] ?? null,
            'synced_at' => now(),
        ];

        // gender and playing_role are optional on the website form but NOT NULL
        // in the CRM. Leaving the key out lets the column default stand rather
        // than inventing a value; the omission is spelled out in the notes.
        foreach (['gender', 'playing_role'] as $field) {
            if (filled($payload[$field] ?? null)) {
                $attributes[$field] = $payload[$field];
            }
        }

        return $attributes;
    }

    /**
     * Everything the website collected that the CRM has no column for, folded
     * into the student's notes so the admin can still read it — and edit it,
     * since notes is an ordinary field on the student form.
     *
     * @param  array<string, mixed>  $payload
     */
    private function notes(array $payload): string
    {
        $lines = ['Admission received from the website on '.now()->format('d M Y H:i').'.'];

        $extras = [
            'Programme' => $payload['programme'] ?? null,
            'Experience level' => $payload['experience_level'] ?? null,
            'Preferred training' => $payload['preferred_training_type'] ?? null,
        ];

        foreach (array_filter($extras, 'filled') as $label => $value) {
            $lines[] = "{$label}: {$value}";
        }

        if (filled($payload['message'] ?? null)) {
            $lines[] = 'Applicant message: '.$payload['message'];
        }

        // Flag the defaulted columns so nobody mistakes a fallback for a choice.
        $missing = array_keys(array_filter(
            ['Gender' => $payload['gender'] ?? null, 'Playing role' => $payload['playing_role'] ?? null],
            fn ($value) => blank($value)
        ));

        if ($missing) {
            $lines[] = 'Not supplied on the website form (CRM default applied, please confirm): '
                .implode(', ', $missing).'.';
        }

        return implode("\n", $lines);
    }
}

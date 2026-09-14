<?php

namespace App\Http\Requests\Api;

use App\Models\Student;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validation for an admission arriving from the public website.
 *
 * The website validates its own form, but nothing reaching this endpoint is
 * trusted: every field is re-checked here against the same enums and column
 * limits the admin panel enforces, so an API admission can never store data
 * the "Add Student" form would have refused.
 */
class WebsiteAdmissionRequest extends FormRequest
{
    /** Same enum the admin form offers. */
    private const BATTING_STYLES = ['right_hand', 'left_hand'];

    private const BOWLING_STYLES = [
        'right_arm_fast', 'right_arm_medium', 'right_arm_off_spin', 'right_arm_leg_spin',
        'left_arm_fast', 'left_arm_medium', 'left_arm_orthodox', 'left_arm_chinaman', 'none',
    ];

    /** The middleware has already authenticated the caller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Idempotency key. The website mints one per admission and reuses
            // it on every retry, so a repeated call can be recognised.
            'website_admission_uuid' => ['required', 'uuid'],
            'source_reference' => ['nullable', 'string', 'max:100'],

            'full_name' => ['required', 'string', 'max:150'],
            'date_of_birth' => ['required', 'date', 'date_format:Y-m-d', 'before:today'],
            // Optional on the website form; the CRM column default applies when
            // absent and the omission is recorded on the student's notes.
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'school_name' => ['nullable', 'string', 'max:255'],

            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_phone' => ['required', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email:rfc', 'max:255'],
            'guardian_relation' => ['nullable', 'string', 'max:50'],

            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],

            'playing_role' => ['nullable', Rule::in(array_keys(Student::PLAYING_ROLES))],
            'batting_style' => ['nullable', Rule::in(self::BATTING_STYLES)],
            'bowling_style' => ['nullable', Rule::in(self::BOWLING_STYLES)],

            'admission_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],

            // Website-only context with no CRM column of its own; it is written
            // into the student's notes so nothing the applicant typed is lost.
            'programme' => ['nullable', 'string', 'max:150'],
            'experience_level' => ['nullable', 'string', 'max:50'],
            'preferred_training_type' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],

            // The website sends a readable batch code, never a CRM id. It is
            // resolved against existing batches; an unknown code is a
            // validation error rather than a silently created master record.
            'batch_code' => ['nullable', 'string', 'max:50'],

            // The applicant must have accepted the academy's terms.
            'terms_accepted' => ['required', 'accepted'],

            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_accepted.accepted' => 'The applicant must accept the terms and conditions.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'photo.max' => 'The photo must not be larger than 2 MB.',
        ];
    }

    /**
     * Answer validation failures in the integration's own envelope rather than
     * Laravel's default shape, so the website always parses one format.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The submitted admission data is invalid.',
            'errors' => $validator->errors()->toArray(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}

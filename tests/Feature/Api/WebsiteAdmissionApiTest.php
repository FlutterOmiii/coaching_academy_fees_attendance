<?php

namespace Tests\Feature\Api;

use App\Models\Batch;
use App\Models\Student;
use App\Services\StudentRegistrar;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class WebsiteAdmissionApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-website-admission-token';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.website_admission.token', self::TOKEN);
        Storage::fake('public');
    }

    private function endpoint(): string
    {
        return route('api.v1.website.admissions.store');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'website_admission_uuid' => (string) Str::uuid(),
            'full_name' => 'Kabir Mehta',
            'date_of_birth' => '2014-03-10',
            'gender' => 'male',
            'blood_group' => 'O+',
            'school_name' => 'Cathedral School',
            'guardian_name' => 'Anil Mehta',
            'guardian_phone' => '9820011223',
            'guardian_email' => 'anil.mehta@example.com',
            'guardian_relation' => 'father',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'playing_role' => 'batter',
            'batting_style' => 'right_hand',
            'bowling_style' => 'none',
            'medical_notes' => 'Mild dust allergy',
            'programme' => 'Summer Camp',
            'experience_level' => 'beginner',
            'preferred_training_type' => 'group_coaching',
            'message' => 'Interested in weekend batches.',
            'terms_accepted' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postAdmission(array $payload, ?string $token = self::TOKEN)
    {
        $headers = ['Accept' => 'application/json'];

        if ($token !== null) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $this->post($this->endpoint(), $payload, $headers);
    }

    // ------------------------------------------------------ Authentication

    public function test_a_request_without_a_token_is_rejected(): void
    {
        $this->postAdmission($this->payload(), token: null)
            ->assertUnauthorized()
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_request_with_the_wrong_token_is_rejected(): void
    {
        $this->postAdmission($this->payload(), token: 'not-the-token')
            ->assertUnauthorized()
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_an_unconfigured_crm_token_never_means_open_access(): void
    {
        config()->set('services.website_admission.token', '');

        $this->postAdmission($this->payload(), token: '')->assertUnauthorized();
        $this->postAdmission($this->payload(), token: null)->assertUnauthorized();

        $this->assertDatabaseCount('students', 0);
    }

    public function test_the_endpoint_is_not_reachable_through_the_admin_session_guard(): void
    {
        // No admin login is involved, and no CSRF token is required: the route
        // lives in the stateless API group.
        $this->postAdmission($this->payload())->assertCreated();
    }

    // ------------------------------------------------------------- Creation

    public function test_a_valid_authenticated_request_creates_a_student(): void
    {
        $payload = $this->payload();

        $response = $this->postAdmission($payload);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Admission transferred to CRM successfully.',
                'data' => [
                    'website_admission_uuid' => $payload['website_admission_uuid'],
                    'sync_status' => 'synced',
                ],
            ])
            ->assertJsonStructure(['data' => ['student_id', 'admission_number']]);

        $student = Student::firstOrFail();

        $this->assertSame('Kabir', $student->first_name);
        $this->assertSame('Mehta', $student->last_name);
        $this->assertSame('2014-03-10', $student->date_of_birth->toDateString());
        $this->assertSame('male', $student->gender);
        $this->assertSame('O+', $student->blood_group);
        $this->assertSame('Cathedral School', $student->school_name);
        $this->assertSame('Anil Mehta', $student->guardian_name);
        $this->assertSame('9820011223', $student->guardian_phone);
        $this->assertSame('anil.mehta@example.com', $student->guardian_email);
        $this->assertSame('father', $student->guardian_relation);
        $this->assertSame('Pune', $student->city);
        $this->assertSame('Maharashtra', $student->state);
        $this->assertSame('411001', $student->pincode);
        $this->assertSame('batter', $student->playing_role);
        $this->assertSame('right_hand', $student->batting_style);
        $this->assertSame('none', $student->bowling_style);
        $this->assertSame('Mild dust allergy', $student->medical_notes);

        // Website-only fields survive in the notes the admin can read and edit.
        $this->assertStringContainsString('Summer Camp', $student->notes);
        $this->assertStringContainsString('beginner', $student->notes);
        $this->assertStringContainsString('group_coaching', $student->notes);
        $this->assertStringContainsString('Interested in weekend batches.', $student->notes);

        // Integration bookkeeping.
        $this->assertSame($payload['website_admission_uuid'], $student->website_admission_uuid);
        $this->assertSame(Student::SOURCE_WEBSITE, $student->admission_source);
        $this->assertNotNull($student->synced_at);

        // A website admission arrives for review, never pre-approved.
        $this->assertSame('pending', $student->admission_status);
        $this->assertSame('STU0001', $student->student_code);
    }

    public function test_the_created_student_appears_in_the_admin_student_listing_query(): void
    {
        $this->postAdmission($this->payload())->assertCreated();

        // The same query the /admin/students page runs.
        $listed = Student::query()->search('Kabir')->get();

        $this->assertCount(1, $listed);
        $this->assertSame('Kabir Mehta', $listed->first()->full_name);
    }

    public function test_optional_dropdowns_fall_back_to_crm_defaults_and_say_so(): void
    {
        $this->postAdmission($this->payload(['gender' => null, 'playing_role' => null]))
            ->assertCreated();

        $student = Student::firstOrFail();

        $this->assertSame('male', $student->gender);
        $this->assertSame('batter', $student->playing_role);
        $this->assertStringContainsString('Gender', $student->notes);
        $this->assertStringContainsString('Playing role', $student->notes);
        $this->assertStringContainsString('CRM default applied', $student->notes);
    }

    public function test_a_payload_cannot_mass_assign_fields_the_website_does_not_own(): void
    {
        $this->postAdmission($this->payload([
            'student_code' => 'HACK9999',
            'admission_status' => 'approved',
            'admission_source' => 'manual',
        ]))->assertCreated();

        $student = Student::firstOrFail();

        $this->assertSame('STU0001', $student->student_code);
        $this->assertSame('pending', $student->admission_status);
        $this->assertSame(Student::SOURCE_WEBSITE, $student->admission_source);
    }

    // ----------------------------------------------------------- Validation

    public function test_a_payload_missing_required_fields_is_rejected(): void
    {
        $this->postAdmission([])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'The submitted admission data is invalid.'])
            ->assertJsonValidationErrors([
                'website_admission_uuid', 'full_name', 'date_of_birth',
                'guardian_name', 'guardian_phone', 'terms_accepted',
            ]);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_invalid_enum_values_are_rejected(): void
    {
        $this->postAdmission($this->payload([
            'gender' => 'unknown',
            'playing_role' => 'captain',
            'batting_style' => 'both',
            'bowling_style' => 'magic',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gender', 'playing_role', 'batting_style', 'bowling_style']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_an_unaccepted_consent_is_rejected(): void
    {
        $this->postAdmission($this->payload(['terms_accepted' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('terms_accepted');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_future_date_of_birth_is_rejected(): void
    {
        $this->postAdmission($this->payload(['date_of_birth' => now()->addYear()->format('Y-m-d')]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_of_birth');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_malformed_uuid_is_rejected(): void
    {
        $this->postAdmission($this->payload(['website_admission_uuid' => 'not-a-uuid']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('website_admission_uuid');
    }

    public function test_an_error_response_never_leaks_internals(): void
    {
        $body = $this->postAdmission([])->getContent();

        $this->assertStringNotContainsString(self::TOKEN, $body);
        $this->assertStringNotContainsString('Stack trace', $body);
        $this->assertStringNotContainsString('/home/', $body);
    }

    // --------------------------------------------------------- Foreign keys

    public function test_a_known_batch_code_enrols_the_student(): void
    {
        $batch = Batch::factory()->create(['code' => 'U14-MORN']);

        $this->postAdmission($this->payload(['batch_code' => 'U14-MORN']))->assertCreated();

        $student = Student::firstOrFail();

        $this->assertTrue($student->activeBatches->contains($batch));
        $this->assertDatabaseHas('batch_student', [
            'student_id' => $student->id,
            'batch_id' => $batch->id,
            'status' => 'active',
        ]);
    }

    public function test_an_unknown_batch_code_is_a_validation_error_and_creates_nothing(): void
    {
        $this->postAdmission($this->payload(['batch_code' => 'NO-SUCH-BATCH']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('batch_code');

        // Never silently invents master data.
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('batches', 0);
    }

    // --------------------------------------------------------- Idempotency

    public function test_the_same_uuid_never_creates_a_second_student(): void
    {
        $payload = $this->payload();

        $first = $this->postAdmission($payload)->assertCreated();
        $second = $this->postAdmission($payload)->assertOk();

        $this->assertDatabaseCount('students', 1);

        $second->assertJson([
            'success' => true,
            'message' => 'Admission was already transferred to CRM.',
            'data' => [
                'student_id' => $first->json('data.student_id'),
                'admission_number' => $first->json('data.admission_number'),
                'sync_status' => 'already_synced',
            ],
        ]);
    }

    public function test_a_retry_after_the_student_was_deleted_does_not_resurrect_a_duplicate(): void
    {
        $payload = $this->payload();

        $this->postAdmission($payload)->assertCreated();
        Student::firstOrFail()->delete(); // soft delete, as the panel does

        $this->postAdmission($payload)->assertOk()->assertJson(['data' => ['sync_status' => 'already_synced']]);

        $this->assertSame(1, Student::withTrashed()->count());
    }

    public function test_two_different_admissions_sharing_a_phone_number_both_import(): void
    {
        // Siblings share a guardian: idempotency must key on the UUID alone.
        $this->postAdmission($this->payload(['full_name' => 'Kabir Mehta']))->assertCreated();
        $this->postAdmission($this->payload(['full_name' => 'Ira Mehta']))->assertCreated();

        $this->assertDatabaseCount('students', 2);
    }

    // ------------------------------------------------------------ Transaction

    public function test_a_failed_write_rolls_back_and_returns_a_structured_error(): void
    {
        $this->mock(StudentRegistrar::class, function ($mock) {
            $mock->shouldReceive('register')->once()->andThrow(new RuntimeException('database exploded'));
        });

        $this->postAdmission($this->payload())
            ->assertStatus(500)
            ->assertExactJson([
                'success' => false,
                'message' => 'The admission could not be processed. Please retry.',
            ]);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_failing_transaction_leaves_no_partial_rows_and_deletes_the_uploaded_photo(): void
    {
        $batch = Batch::factory()->create();

        // guardian_name is NOT NULL, so the insert fails after the photo has
        // already been written to the disk — exactly the case the registrar's
        // cleanup exists for.
        $attributes = [
            'first_name' => 'Kabir',
            'last_name' => 'Mehta',
            'date_of_birth' => '2014-03-10',
            'guardian_name' => null,
            'guardian_phone' => '9820011223',
            'admission_date' => now()->toDateString(),
        ];

        try {
            app(StudentRegistrar::class)->register(
                $attributes,
                UploadedFile::fake()->image('kabir.jpg'),
                (int) $batch->id,
            );
            $this->fail('The registrar should have propagated the database failure.');
        } catch (QueryException) {
            // expected
        }

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('batch_student', 0);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    // ------------------------------------------------------------- Uploads

    public function test_a_valid_photo_is_stored_under_the_crm_storage_conventions(): void
    {
        $this->postAdmission($this->payload([
            'photo' => UploadedFile::fake()->image('kabir.jpg', 400, 400),
        ]))->assertCreated();

        $student = Student::firstOrFail();

        $this->assertNotNull($student->photo);
        $this->assertStringStartsWith('students/', $student->photo);
        // The original filename is never trusted.
        $this->assertStringNotContainsString('kabir', $student->photo);
        Storage::disk('public')->assertExists($student->photo);
    }

    public function test_an_executable_disguised_as_a_photo_is_rejected(): void
    {
        $this->postAdmission($this->payload([
            'photo' => UploadedFile::fake()->createWithContent('payload.php', '<?php echo "pwned";'),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');

        $this->assertDatabaseCount('students', 0);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_an_oversized_photo_is_rejected(): void
    {
        $this->postAdmission($this->payload([
            'photo' => UploadedFile::fake()->image('huge.jpg')->size(4096),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');

        $this->assertDatabaseCount('students', 0);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}

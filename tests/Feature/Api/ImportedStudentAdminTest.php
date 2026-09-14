<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\Batch;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A student imported through the API must be an ordinary CRM student: the
 * existing listing, detail, edit, update and delete screens work on it exactly
 * as they do on one typed into the admin form, and manually created students
 * carry on unaffected.
 */
class ImportedStudentAdminTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-website-admission-token';

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.website_admission.token', self::TOKEN);

        $this->admin = Admin::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'secret-password',
            'role' => Admin::ROLE_OWNER,
            'status' => 'active',
        ]);
    }

    private function importStudent(array $overrides = []): Student
    {
        $this->post(route('api.v1.website.admissions.store'), array_merge([
            'website_admission_uuid' => (string) Str::uuid(),
            'full_name' => 'Kabir Mehta',
            'date_of_birth' => '2014-03-10',
            'gender' => 'male',
            'guardian_name' => 'Anil Mehta',
            'guardian_phone' => '9820011223',
            'playing_role' => 'batter',
            'terms_accepted' => true,
        ], $overrides), [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.self::TOKEN,
        ])->assertCreated();

        return Student::latest('id')->firstOrFail();
    }

    public function test_an_imported_student_is_listed_on_the_admin_students_page(): void
    {
        $student = $this->importStudent();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee($student->student_code)
            ->assertSee('Kabir Mehta')
            // The unobtrusive source indicator.
            ->assertSee('Website');
    }

    public function test_an_imported_student_can_be_found_through_search_and_filters(): void
    {
        $student = $this->importStudent();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.students.index', ['search' => 'Mehta', 'status' => 'active', 'admission_status' => 'pending']))
            ->assertOk()
            ->assertSee($student->student_code);
    }

    public function test_an_imported_student_opens_in_the_existing_detail_and_edit_screens(): void
    {
        $student = $this->importStudent();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Kabir Mehta');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.students.edit', $student))
            ->assertOk()
            ->assertSee('Kabir Mehta');
    }

    public function test_an_imported_student_can_be_edited_and_saved_through_the_existing_form(): void
    {
        $student = $this->importStudent();
        $batch = Batch::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.students.update', $student), [
                'full_name' => 'Kabir A Mehta',
                'date_of_birth' => '2014-03-10',
                'gender' => 'male',
                'guardian_name' => 'Anil Mehta',
                'guardian_phone' => '9820011224',
                'playing_role' => 'wicket_keeper',
                'admission_date' => '2026-09-14',
                'admission_status' => 'approved',
                'status' => 'active',
                'batch_id' => $batch->id,
            ])
            ->assertRedirect(route('admin.students.show', $student));

        $student->refresh();

        $this->assertSame('Kabir', $student->first_name);
        $this->assertSame('A Mehta', $student->last_name);
        $this->assertSame('wicket_keeper', $student->playing_role);
        $this->assertSame('approved', $student->admission_status);
        $this->assertTrue($student->activeBatches()->where('batches.id', $batch->id)->exists());

        // Editing must not disturb the integration bookkeeping.
        $this->assertNotNull($student->website_admission_uuid);
        $this->assertSame(Student::SOURCE_WEBSITE, $student->admission_source);
    }

    public function test_an_imported_student_can_be_soft_deleted_from_the_admin_panel(): void
    {
        $student = $this->importStudent();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'));

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    public function test_an_imported_student_is_included_in_the_csv_export(): void
    {
        $student = $this->importStudent();

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.students.export'));

        $response->assertOk();
        $this->assertStringContainsString($student->student_code, $response->streamedContent());
    }

    public function test_manually_created_students_are_unaffected_by_the_integration_columns(): void
    {
        $batch = Batch::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.students.store'), [
                'full_name' => 'Ira Kulkarni',
                'date_of_birth' => '2013-05-02',
                'gender' => 'female',
                'guardian_name' => 'Sonia Kulkarni',
                'guardian_phone' => '9820011225',
                'playing_role' => 'bowler',
                'admission_date' => '2026-09-14',
                'admission_status' => 'approved',
                'status' => 'active',
                'batch_id' => $batch->id,
            ])
            ->assertRedirect();

        $student = Student::where('first_name', 'Ira')->firstOrFail();

        $this->assertNull($student->website_admission_uuid);
        $this->assertNull($student->admission_source);
        $this->assertNull($student->synced_at);
        $this->assertSame('STU0001', $student->student_code);
        $this->assertTrue($student->activeBatches()->where('batches.id', $batch->id)->exists());
    }

    public function test_the_website_scope_separates_the_two_intakes(): void
    {
        $this->importStudent();
        Student::factory()->create();

        $this->assertSame(1, Student::fromWebsite()->count());
        $this->assertSame(2, Student::count());
    }
}

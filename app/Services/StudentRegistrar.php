<?php

namespace App\Services;

use App\Helpers\StorageHelper;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The one place a student record comes into existence.
 *
 * Both the admin "Add Student" form and the website admission API go through
 * here, so the code allocation, the photo upload and the first batch
 * enrolment follow exactly the same rules no matter where the student came
 * from. Everything runs inside a single transaction; a photo written to the
 * disk is removed again if any of the writes fail, so a rolled-back admission
 * never leaves an orphan file behind.
 */
class StudentRegistrar
{
    /**
     * @param  array<string, mixed>  $attributes  already-validated columns
     * @param  int|null  $batchId  batch to enrol into, if one was chosen
     */
    public function register(array $attributes, ?UploadedFile $photo = null, ?int $batchId = null): Student
    {
        $uploadedPath = null;

        try {
            return DB::transaction(function () use ($attributes, $photo, $batchId, &$uploadedPath) {
                $attributes['student_code'] = Student::nextCode();

                if ($photo) {
                    $uploadedPath = StorageHelper::upload($photo, 'students');
                    $attributes['photo'] = $uploadedPath;
                }

                $student = Student::create($attributes);

                if ($batchId) {
                    $student->batches()->attach($batchId, [
                        'joined_on' => $attributes['admission_date'],
                        'status' => 'active',
                    ]);
                }

                return $student;
            });
        } catch (Throwable $e) {
            if ($uploadedPath) {
                StorageHelper::delete($uploadedPath);
            }

            throw $e;
        }
    }
}

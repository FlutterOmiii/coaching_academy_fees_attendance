<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the website is told about a student it created. Deliberately narrow:
 * enough to record the link on the website's side, and nothing more — no
 * personal data is echoed back over the wire.
 *
 * @property Student $resource
 */
class WebsiteAdmissionResource extends JsonResource
{
    public function __construct(Student $student, private readonly bool $created)
    {
        parent::__construct($student);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'student_id' => $this->resource->id,
            'admission_number' => $this->resource->student_code,
            'website_admission_uuid' => $this->resource->website_admission_uuid,
            'sync_status' => $this->created ? 'synced' : 'already_synced',
        ];
    }
}

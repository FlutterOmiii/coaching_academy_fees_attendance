<?php

namespace App\Models;

use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use HasFactory;

    public const TYPES = [
        'photo' => 'Photograph',
        'birth_certificate' => 'Birth Certificate',
        'id_proof' => 'ID Proof',
        'address_proof' => 'Address Proof',
        'medical_certificate' => 'Medical Certificate',
        'school_id' => 'School ID',
        'other' => 'Other',
    ];

    protected $fillable = [
        'student_id', 'type', 'title', 'file_path', 'mime_type', 'file_size', 'uploaded_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function getUrlAttribute(): string
    {
        return StorageHelper::url($this->file_path);
    }

    public function getReadableSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        return $bytes < 1048576
            ? round($bytes / 1024, 1).' KB'
            : round($bytes / 1048576, 1).' MB';
    }
}

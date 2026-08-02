<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'from_batch_id', 'to_batch_id', 'transferred_on', 'reason', 'transferred_by',
    ];

    protected function casts(): array
    {
        return [
            'transferred_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'from_batch_id');
    }

    public function toBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'to_batch_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'transferred_by');
    }
}

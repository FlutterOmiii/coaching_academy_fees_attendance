<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'upi' => 'UPI',
        'card' => 'Card',
        'net_banking' => 'Net Banking',
        'cheque' => 'Cheque',
        'bank_transfer' => 'Bank Transfer',
    ];

    protected $fillable = [
        'expense_category_id', 'coach_id', 'salary_month', 'title', 'amount',
        'expense_date', 'payment_method', 'vendor', 'reference_no', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'salary_month' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    // ---------------------------------------------------------------- Relations

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /** Set only on coach-salary payments. */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    // ------------------------------------------------------------------ Scopes

    /** Only coach-salary payments. */
    public function scopeSalaries(Builder $query): Builder
    {
        return $query->whereNotNull('coach_id');
    }

    public function scopeForMonth(Builder $query, $year, $month): Builder
    {
        return $query->whereYear('expense_date', $year)->whereMonth('expense_date', $month);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('expense_date', [$from, $to]);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('vendor', 'like', "%{$term}%")
                ->orWhere('reference_no', 'like', "%{$term}%");
        });
    }

    // --------------------------------------------------------------- Accessors

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }
}

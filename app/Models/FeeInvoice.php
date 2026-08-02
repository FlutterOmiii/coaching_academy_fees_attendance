<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending' => 'Pending',
        'partial' => 'Partially Paid',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
    ];

    /** Statuses still owing money. */
    public const OUTSTANDING_STATUSES = ['pending', 'partial', 'overdue'];

    /**
     * balance_amount is deliberately absent: it is a database-generated column
     * and writing to it would fail.
     */
    protected $fillable = [
        'invoice_no', 'student_id', 'batch_id', 'fee_structure_id', 'billing_period',
        'amount', 'discount', 'late_fee', 'total_amount', 'paid_amount',
        'issue_date', 'due_date', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billing_period' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'late_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
        ];
    }

    // ---------------------------------------------------------------- Relations

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(FeeReminder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    // ------------------------------------------------------------------ Scopes

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', self::OUTSTANDING_STATUSES);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()->whereDate('due_date', '<', today());
    }

    public function scopeForPeriod(Builder $query, $year, $month): Builder
    {
        return $query->whereYear('billing_period', $year)->whereMonth('billing_period', $month);
    }

    // ----------------------------------------------------------------- Helpers

    /** Grace days allowed after the due date before a fee counts as overdue. */
    public static function graceDays(): int
    {
        return (int) Setting::get('fee_grace_days', 0);
    }

    /** The day of the month fees fall due. */
    public static function dueDay(): int
    {
        return max(1, min(28, (int) Setting::get('fee_due_day', 10)));
    }

    /** Due date for a given billing period, from the configured due day. */
    public static function dueDateFor(Carbon $period): Carbon
    {
        return $period->copy()->startOfMonth()->addDays(self::dueDay() - 1);
    }

    public function isFullyPaid(): bool
    {
        return $this->balance_amount <= 0;
    }

    /**
     * Status derived from the balance and the clock, rather than read from the
     * status column. The stored value only refreshes when a payment is written,
     * so an unpaid invoice would still read "pending" long after its due date.
     */
    public function getLiveStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->balance_amount <= 0) {
            return 'paid';
        }

        if ($this->due_date && $this->due_date->copy()->addDays(self::graceDays())->lt(today())) {
            return 'overdue';
        }

        return $this->paid_amount > 0 ? 'partial' : 'pending';
    }

    /** Whole days past the due date, 0 when not yet due. */
    public function getDaysOverdueAttribute(): int
    {
        if (! $this->due_date || $this->balance_amount <= 0 || ! $this->due_date->isPast()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(today());
    }

    /** Whole days until the due date, 0 once it has passed. */
    public function getDaysRemainingAttribute(): int
    {
        if (! $this->due_date || $this->due_date->isPast()) {
            return 0;
        }

        return (int) today()->diffInDays($this->due_date);
    }

    /**
     * Recalculate paid_amount from completed payments and move the invoice to
     * the matching status. Called whenever a payment is written or removed.
     */
    public function syncStatus(): void
    {
        $paid = (float) $this->payments()->where('status', 'completed')->sum('amount');

        $this->paid_amount = $paid;

        if ($this->status !== 'cancelled') {
            $balance = (float) $this->total_amount - $paid;

            $this->status = match (true) {
                $balance <= 0 => 'paid',
                $paid > 0 => 'partial',
                // Overdue only once the due date plus the grace period has
                // passed — same rule as the live_status accessor.
                $this->due_date && $this->due_date->copy()->addDays(self::graceDays())->lt(today()) => 'overdue',
                default => 'pending',
            };
        }

        $this->save();
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->billing_period?->format('F Y') ?? '';
    }

    /**
     * A humble fee-reminder message for the parent. Single source of truth,
     * used both for the logged reminder and the WhatsApp deep link, so the
     * parent reads exactly what the academy recorded.
     */
    public function reminderMessage(): string
    {
        $academy = Setting::get('academy_name', 'our academy');
        $currency = Setting::get('currency_symbol', '₹');
        $guardian = $this->student?->guardian_name ?: 'Parent';
        $child = $this->student?->full_name ?: 'your child';
        $amount = $currency.number_format((float) $this->balance_amount);

        return "Dear {$guardian}, 🙏\n\n"
            ."Warm greetings from {$academy}.\n\n"
            ."This is a gentle reminder that the fee of {$amount} for {$child} "
            ."for the month of {$this->period_label} is still remaining.\n\n"
            ."We kindly request you to please pay it at your earliest convenience. "
            ."If you have already made the payment, please ignore this message.\n\n"
            ."Thank you for your continued support. 🏏\n\n"
            ."Warm regards,\n{$academy}";
    }

    public static function nextInvoiceNo(): string
    {
        $last = static::max('id') ?? 0;

        return 'INV-'.now()->format('Y').'-'.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }
}

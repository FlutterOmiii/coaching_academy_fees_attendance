<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's fee for one match. Mirrors FeePayment's vocabulary (modes,
 * receipt numbers) so staff see one consistent system, while staying out of
 * the monthly-invoice pipeline entirely.
 */
class MatchFee extends Model
{
    public const MODES = FeePayment::MODES;

    protected $fillable = [
        'fee_match_id', 'student_id', 'amount', 'status', 'payment_date',
        'mode', 'reference_no', 'receipt_no', 'notes', 'collected_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    // ---------------------------------------------------------------- Relations

    public function match(): BelongsTo
    {
        return $this->belongsTo(FeeMatch::class, 'fee_match_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'collected_by');
    }

    // ------------------------------------------------------------------ Scopes

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    // --------------------------------------------------------------- Accessors

    public function getModeLabelAttribute(): string
    {
        return self::MODES[$this->mode] ?? '—';
    }

    /** The WhatsApp payment confirmation — the match-fee receipt as a message. */
    public function receiptMessage(): string
    {
        $academy = Setting::get('academy_name', 'our academy');
        $currency = Setting::get('currency_symbol', '₹');
        $guardian = $this->student?->guardian_name ?: 'Parent';
        $child = $this->student?->full_name ?: 'your child';

        $lines = [
            "Dear {$guardian},",
            '',
            "Warm greetings from *{$academy}*. We have received the match fee of "
                ."*{$currency}".number_format((float) $this->amount)."* for *{$child}* "
                ."for *{$this->match?->title}* "
                .'('.$this->match?->match_date?->format('d M Y').'). ✅',
            '',
            '🧾 *Payment Receipt*',
            "Receipt No: {$this->receipt_no}",
            'Date: '.$this->payment_date?->format('d M Y'),
            "Mode: {$this->mode_label}",
        ];

        if ($this->reference_no) {
            $lines[] = "Ref: {$this->reference_no}";
        }

        $lines[] = '';
        $lines[] = 'The match fee is now *fully paid*. 🙏';
        $lines[] = '';
        $lines[] = 'Thank you for your continued support.';
        $lines[] = '';
        $lines[] = '*Warm regards,*';
        $lines[] = "*{$academy}*";

        return implode("\n", $lines);
    }

    /**
     * Same shape as FeePayment receipts, prefixed so the series stay distinct.
     * Numbered from the receipt sequence itself — unlike fee_payments, these
     * rows are updated in place, so max(id) would repeat numbers.
     */
    public static function nextReceiptNo(): string
    {
        $prefix = 'MFR-'.now()->format('Y').'-';

        $last = (int) static::where('receipt_no', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(receipt_no, '.(strlen($prefix) + 1).') AS UNSIGNED)) AS m')
            ->value('m');

        return $prefix.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }
}

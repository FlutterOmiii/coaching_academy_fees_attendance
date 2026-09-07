<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    use HasFactory;

    public const MODES = [
        'cash' => 'Cash',
        'upi' => 'UPI',
        'card' => 'Card',
        'net_banking' => 'Net Banking',
        'cheque' => 'Cheque',
        'bank_transfer' => 'Bank Transfer',
    ];

    protected $fillable = [
        'receipt_no', 'fee_invoice_id', 'student_id', 'amount', 'payment_date',
        'mode', 'reference_no', 'status', 'notes', 'received_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Keep the parent invoice's paid amount and status correct whenever a
     * payment is written or removed, so the two can never disagree.
     */
    protected static function booted(): void
    {
        $sync = fn (FeePayment $payment) => $payment->invoice?->syncStatus();

        static::saved($sync);
        static::deleted($sync);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'received_by');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('payment_date', [$from, $to]);
    }

    public function getModeLabelAttribute(): string
    {
        return self::MODES[$this->mode] ?? ucfirst((string) $this->mode);
    }

    /**
     * The WhatsApp payment confirmation — the receipt as a message, in the
     * house format. Says "fully paid" once the invoice balance reaches zero.
     */
    public function receiptMessage(): string
    {
        $academy = Setting::get('academy_name', 'our academy');
        $currency = Setting::get('currency_symbol', '₹');
        $guardian = $this->student?->guardian_name ?: 'Parent';
        $child = $this->student?->full_name ?: 'your child';
        $month = $this->invoice?->period_label ?? '';
        $balance = (float) ($this->invoice?->balance_amount ?? 0);

        $lines = [
            "Dear {$guardian},",
            '',
            "Warm greetings from *{$academy}*. We have received the payment of "
                ."*{$currency}".number_format((float) $this->amount).'* for '
                ."*{$child}*".($month ? " for the month of *{$month}*" : '').'. ✅',
            '',
            '🧾 *Payment Receipt*',
            "Receipt No: {$this->receipt_no}",
            'Date: '.$this->payment_date?->format('d M Y'),
            'Mode: '.($this->mode_label ?? ucfirst((string) $this->mode)),
        ];

        if ($this->reference_no) {
            $lines[] = "Ref: {$this->reference_no}";
        }

        $lines[] = '';
        $lines[] = $balance <= 0
            ? "The fee".($month ? " for *{$month}*" : '')." is now *fully paid*. 🙏"
            : "Remaining balance: *{$currency}".number_format($balance).'*.';
        $lines[] = '';
        $lines[] = '📄 View / download your receipt:';
        $lines[] = \Illuminate\Support\Facades\URL::signedRoute('public.receipt', ['payment' => $this->id]);
        $lines[] = '';
        $lines[] = 'Thank you for your continued support.';
        $lines[] = '';
        $lines[] = '*Warm regards,*';
        $lines[] = "*{$academy}*";

        return implode("\n", $lines);
    }

    public static function nextReceiptNo(): string
    {
        $last = static::max('id') ?? 0;

        return 'RCP-'.now()->format('Y').'-'.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }
}

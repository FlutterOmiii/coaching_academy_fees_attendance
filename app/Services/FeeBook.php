<?php

namespace App\Services;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Answers the only questions the academy actually asks about fees:
 * who paid, who has not, and who is late.
 *
 * The invoice/payment engine is untouched — this only reads it and presents
 * it per student per month, which is how the staff think about fees.
 *
 * Status is derived from balance + due date + grace rather than the stored
 * status column, because that column only refreshes when a payment is
 * written and would otherwise read "pending" months after the due date.
 */
class FeeBook
{
    /** Students of a month, each with their fee state. */
    public function students(Carbon $period, array $filters = []): Builder
    {
        $query = Student::query()
            ->active()
            ->approved()
            ->with([
                'activeBatches:id,name,monthly_fee',
                'feeInvoices' => fn ($q) => $q->whereDate('billing_period', $period->copy()->startOfMonth())
                    ->with('payments:id,fee_invoice_id,payment_date,amount'),
            ])
            ->search($filters['search'] ?? null);

        if (! empty($filters['batch_id'])) {
            $query->whereHas('batches', fn ($b) => $b->where('batches.id', $filters['batch_id'])
                ->where('batch_student.status', 'active'));
        }

        if (! empty($filters['status'])) {
            $this->applyStatusFilter($query, $period, $filters['status']);
        }

        return $query->orderBy('first_name');
    }

    /**
     * Filter on the derived status using SQL that mirrors live_status, so the
     * list can never disagree with the badge shown on the row.
     */
    private function applyStatusFilter(Builder $query, Carbon $period, string $status): void
    {
        $start = $period->copy()->startOfMonth()->toDateString();
        $cutoff = today()->subDays(FeeInvoice::graceDays())->toDateString();

        $forPeriod = fn ($q) => $q->whereDate('billing_period', $start)->where('status', '!=', 'cancelled');

        match ($status) {
            'paid' => $query->whereHas('feeInvoices', fn ($q) => $forPeriod($q)->where('balance_amount', '<=', 0)),

            'overdue' => $query->whereHas('feeInvoices', fn ($q) => $forPeriod($q)
                ->where('balance_amount', '>', 0)
                ->whereDate('due_date', '<', $cutoff)),

            // Pending covers both "billed but not yet late" and "never billed".
            'pending' => $query->where(fn ($w) => $w
                ->whereHas('feeInvoices', fn ($q) => $forPeriod($q)
                    ->where('balance_amount', '>', 0)
                    ->whereDate('due_date', '>=', $cutoff))
                ->orWhereDoesntHave('feeInvoices', fn ($q) => $q->whereDate('billing_period', $start))),

            default => null,
        };
    }

    /**
     * Flatten a student into the single row the screen shows.
     *
     * @return array{invoice: ?FeeInvoice, batch: ?object, fee: float, status: string, due_date: ?Carbon, last_payment: ?FeePayment}
     */
    public function row(Student $student, Carbon $period): array
    {
        $invoice = $student->feeInvoices->first();
        $batch = $student->activeBatches->first();

        $lastPayment = $invoice?->payments
            ->sortByDesc('payment_date')
            ->first();

        return [
            'invoice' => $invoice,
            'batch' => $batch,
            'fee' => (float) ($invoice->total_amount ?? $batch->monthly_fee ?? 0),
            'status' => $invoice?->live_status ?? $this->unbilledStatus($period),
            'due_date' => $invoice?->due_date ?? FeeInvoice::dueDateFor($period),
            'last_payment' => $lastPayment,
        ];
    }

    /** A student with no invoice yet is pending, or overdue once grace lapses. */
    private function unbilledStatus(Carbon $period): string
    {
        $due = FeeInvoice::dueDateFor($period);

        return $due->copy()->addDays(FeeInvoice::graceDays())->lt(today()) ? 'overdue' : 'pending';
    }

    /** Headline counters for the month. */
    public function summary(Carbon $period): array
    {
        $start = $period->copy()->startOfMonth();

        $totalStudents = Student::active()->approved()->count();

        $collected = (float) FeePayment::completed()
            ->whereYear('payment_date', $start->year)
            ->whereMonth('payment_date', $start->month)
            ->sum('amount');

        $paid = $this->students($start, ['status' => 'paid'])->count();
        $overdue = $this->students($start, ['status' => 'overdue'])->count();
        $pending = $this->students($start, ['status' => 'pending'])->count();

        return [
            'total_students' => $totalStudents,
            'collected' => $collected,
            'paid' => $paid,
            'pending' => $pending,
            'overdue' => $overdue,
            'outstanding' => (float) FeeInvoice::outstanding()->sum('balance_amount'),
            'due_day' => FeeInvoice::dueDay(),
            'grace_days' => FeeInvoice::graceDays(),
            'currency' => Setting::get('currency_symbol', '₹'),
        ];
    }

    /** Every student who still owes for the month, worst first. */
    public function pending(Carbon $period, array $filters = []): Collection
    {
        $paidFilter = $filters;
        unset($paidFilter['status']);

        return $this->students($period, $paidFilter)
            ->get()
            ->map(fn (Student $s) => ['student' => $s] + $this->row($s, $period))
            ->filter(fn ($row) => in_array($row['status'], ['pending', 'partial', 'overdue'], true))
            ->sortBy(fn ($row) => $row['due_date']?->timestamp ?? 0)
            ->values();
    }
}

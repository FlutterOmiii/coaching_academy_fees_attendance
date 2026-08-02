<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Batch;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeReminder;
use App\Models\FeeStructure;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FeeSeeder extends Seeder
{
    private int $invoiceCounter = 0;

    private int $receiptCounter = 0;

    public function run(): void
    {
        $accountantId = Admin::where('email', 'accounts@academy.com')->value('id');

        $structures = $this->seedStructures();
        $this->seedInvoices($structures, $accountantId);
        $this->seedReminders($accountantId);
    }

    /** One monthly tuition structure per batch, plus academy-wide one-off fees. */
    private function seedStructures(): array
    {
        $structures = [];

        foreach (Batch::all() as $batch) {
            $structures[$batch->id] = FeeStructure::create([
                'name' => $batch->name.' — Monthly Tuition',
                'batch_id' => $batch->id,
                'type' => 'tuition',
                'frequency' => 'monthly',
                'amount' => $batch->monthly_fee,
                'description' => 'Monthly coaching fee for '.$batch->name,
                'status' => 'active',
            ]);
        }

        foreach ([
            ['Admission Fee', 'admission', 'one_time', 5000],
            ['Academy Kit', 'kit', 'one_time', 3500],
            ['Tournament Entry', 'tournament', 'one_time', 1200],
            ['Equipment Charges', 'equipment', 'annual', 2500],
        ] as [$name, $type, $frequency, $amount]) {
            FeeStructure::create([
                'name' => $name,
                'batch_id' => null,
                'type' => $type,
                'frequency' => $frequency,
                'amount' => $amount,
                'description' => $name,
                'status' => 'active',
            ]);
        }

        return $structures;
    }

    /**
     * Raise a monthly invoice for every month a student has been enrolled, then
     * pay most of them off. Recent months are left progressively less paid so
     * the pending-fees figure looks like a real ledger.
     */
    private function seedInvoices(array $structures, ?int $accountantId): void
    {
        $students = Student::with('batches')->where('admission_status', 'approved')->get();

        foreach ($students as $student) {
            $batch = $student->batches->first();

            if (! $batch || ! isset($structures[$batch->id])) {
                continue;
            }

            $structure = $structures[$batch->id];
            $period = $student->admission_date->copy()->startOfMonth();
            $end = Carbon::now()->startOfMonth();

            $admissionMonth = $student->admission_date->copy()->startOfMonth();

            while ($period->lte($end)) {
                // Stop billing once an inactive student has left.
                $monthsEnrolled = (int) abs($admissionMonth->diffInMonths($period));

                if ($student->status === 'inactive' && $monthsEnrolled > 3) {
                    break;
                }

                $this->createInvoice($student, $batch, $structure, $period->copy(), $accountantId);

                $period->addMonth();
            }
        }
    }

    private function createInvoice(
        Student $student,
        Batch $batch,
        FeeStructure $structure,
        Carbon $period,
        ?int $accountantId
    ): void {
        $monthsAgo = (int) abs($period->copy()->startOfMonth()->diffInMonths(Carbon::now()->startOfMonth()));

        // Occasional sibling / early-bird discount.
        $discount = random_int(1, 8) === 1 ? round((float) $structure->amount * 0.1, 2) : 0;
        $amount = (float) $structure->amount;
        $total = $amount - $discount;

        $issueDate = $period->copy()->startOfMonth();
        $dueDate = $period->copy()->startOfMonth()->addDays(9);

        // Older invoices are almost always settled; the current month often is not.
        $payChance = match (true) {
            $monthsAgo >= 3 => 95,
            $monthsAgo === 2 => 88,
            $monthsAgo === 1 => 75,
            default => 45,
        };

        $roll = random_int(1, 100);
        $isPaid = $roll <= $payChance;
        $isPartial = ! $isPaid && random_int(1, 3) === 1;

        $this->invoiceCounter++;

        $invoice = FeeInvoice::create([
            'invoice_no' => 'INV-'.$period->format('Y').'-'.str_pad((string) $this->invoiceCounter, 5, '0', STR_PAD_LEFT),
            'student_id' => $student->id,
            'batch_id' => $batch->id,
            'fee_structure_id' => $structure->id,
            'billing_period' => $period->toDateString(),
            'amount' => $amount,
            'discount' => $discount,
            'late_fee' => 0,
            'total_amount' => $total,
            'paid_amount' => 0,
            'issue_date' => $issueDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'status' => 'pending',
            'created_by' => $accountantId,
        ]);

        if ($isPaid) {
            $this->createPayment($invoice, $total, $dueDate, $accountantId);
        } elseif ($isPartial) {
            $this->createPayment($invoice, round($total / 2, 2), $dueDate, $accountantId);
        } else {
            // Unpaid and past due — apply a late fee and let syncStatus mark it overdue.
            if ($dueDate->isPast()) {
                $invoice->late_fee = 200;
                $invoice->total_amount = $total + 200;
                $invoice->save();
            }

            $invoice->syncStatus();
        }
    }

    private function createPayment(FeeInvoice $invoice, float $amount, Carbon $dueDate, ?int $accountantId): void
    {
        $this->receiptCounter++;

        // Paid somewhere between issue date and shortly after the due date.
        $paidOn = $dueDate->copy()->subDays(random_int(0, 8))->addDays(random_int(0, 6));

        if ($paidOn->isFuture()) {
            $paidOn = Carbon::now();
        }

        // Writing the payment triggers FeePayment::booted(), which recalculates
        // the invoice's paid_amount and status.
        FeePayment::create([
            'receipt_no' => 'RCP-'.$paidOn->format('Y').'-'.str_pad((string) $this->receiptCounter, 5, '0', STR_PAD_LEFT),
            'fee_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'amount' => $amount,
            'payment_date' => $paidOn->toDateString(),
            'mode' => $this->pick(['cash', 'upi', 'upi', 'upi', 'card', 'net_banking', 'bank_transfer', 'cheque']),
            'reference_no' => strtoupper(bin2hex(random_bytes(5))),
            'status' => 'completed',
            'received_by' => $accountantId,
        ]);
    }

    /** Reminders against invoices that are still outstanding. */
    private function seedReminders(?int $accountantId): void
    {
        $overdue = FeeInvoice::overdue()->inRandomOrder()->limit(25)->get();

        foreach ($overdue as $invoice) {
            $channel = $this->pick(['email', 'sms', 'whatsapp', 'call']);

            FeeReminder::create([
                'fee_invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'channel' => $channel,
                'message' => 'Reminder: fees for '.$invoice->period_label.' of Rs. '.$invoice->balance_amount.' are pending.',
                'status' => 'sent',
                'sent_at' => Carbon::now()->subDays(random_int(1, 20)),
                'sent_by' => $accountantId,
            ]);
        }
    }

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }
}

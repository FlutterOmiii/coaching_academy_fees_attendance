<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeReminder;
use App\Models\FeeStructure;
use App\Models\Setting;
use App\Models\Student;
use App\Services\FeeBook;
use App\Support\WhatsApp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeController extends Controller
{
    /**
     * The fee book: one row per student for the chosen month, showing whether
     * they have paid. Replaces the old payment-by-payment ledger, which could
     * not answer "who has not paid?" at a glance.
     */
    public function index(Request $request, FeeBook $book)
    {
        $month = $this->month($request);

        $filters = [
            'search' => $request->string('search')->toString(),
            'batch_id' => $request->input('batch_id'),
            'status' => $request->input('status'),
        ];

        $students = $book->students($month, $filters)->paginate(20)->withQueryString();

        // Flatten each student into the single row the screen renders.
        $rows = $students->getCollection()->map(fn ($s) => ['student' => $s] + $book->row($s, $month));

        return view('admin.fees.index', [
            'students' => $students,
            'rows' => $rows,
            'month' => $month,
            'summary' => $book->summary($month),
            'batches' => Batch::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    /** Only the students who still owe, with contact details and how late they are. */
    public function pending(Request $request, FeeBook $book)
    {
        $month = $this->month($request);

        $rows = $book->pending($month, [
            'search' => $request->string('search')->toString(),
            'batch_id' => $request->input('batch_id'),
        ]);

        return view('admin.fees.pending', [
            'rows' => $rows,
            'month' => $month,
            'summary' => $book->summary($month),
            'batches' => Batch::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Start-of-month reminder blast: every active student with a one-tap
     * WhatsApp message saying the fee is due on the configured due day and
     * humbly asking to pay before then. wa.me cannot auto-send in bulk (that
     * needs the paid Business API), so the page offers a rapid one-tap-per-
     * parent "Send All" flow instead.
     */
    public function reminders(Request $request, FeeBook $book)
    {
        $month = $this->month($request);

        $rows = $book->students($month)->get()->map(function (Student $student) use ($book, $month) {
            $row = $book->row($student, $month);

            return [
                'student' => $student,
                'batch' => $row['batch'],
                'fee' => $row['fee'],
                'status' => $row['status'],
                'due_date' => $row['due_date'],
                'wa_link' => WhatsApp::link(
                    $student->guardian_phone,
                    $this->monthlyReminderText($student, $row['fee'], $month, $row['due_date'])
                ),
            ];
        });

        return view('admin.fees.reminders', [
            'month' => $month,
            'rows' => $rows,
            'dueDate' => FeeInvoice::dueDateFor($month),
            'currency' => Setting::get('currency_symbol', '₹'),
        ]);
    }

    /** The humble start-of-month message: fee due on day X, please pay before it. */
    private function monthlyReminderText(Student $student, float $fee, Carbon $month, Carbon $due): string
    {
        $academy = Setting::get('academy_name', 'our academy');
        $currency = Setting::get('currency_symbol', '₹');
        $guardian = $student->guardian_name ?: 'Parent';
        $amount = $fee > 0 ? ' of *'.$currency.number_format($fee).'*' : '';

        return "Dear {$guardian},\n\n"
            ."Warm greetings from *{$academy}*. "
            ."This is a gentle reminder that the monthly fee{$amount} for "
            ."*{$student->full_name}* for the month of *{$month->format('F Y')}* "
            ."is due on *{$due->format('d M Y')}*. "
            ."We kindly request you to please pay before *{$due->format('d M')}* "
            ."at your earliest convenience.\n\n"
            ."If you have already made the payment, please share the screenshot. "
            ."Thank you for your continued support.\n\n"
            ."*Warm regards,*\n"
            ."*{$academy}*";
    }

    /** A student's full payment history, for the History popup. */
    public function history(Student $student)
    {
        $payments = $student->payments()
            ->with('invoice:id,billing_period,invoice_no')
            ->completed()
            ->latest('payment_date')
            ->latest('id')
            ->get()
            ->map(fn (FeePayment $p) => [
                'receipt_no' => $p->receipt_no,
                'date' => $p->payment_date->format('d M Y'),
                'month' => $p->invoice?->billing_period?->format('F Y') ?? '—',
                'amount' => number_format((float) $p->amount),
                'mode' => $p->mode_label,
                'receipt_url' => route('admin.fees.receipt', $p),
            ]);

        return response()->json([
            'student' => $student->full_name,
            'code' => $student->student_code,
            'total' => number_format((float) $payments->sum(fn ($p) => (float) str_replace(',', '', $p['amount']))),
            'payments' => $payments,
        ]);
    }

    private function month(Request $request): Carbon
    {
        return $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();
    }

    public function invoices(Request $request)
    {
        $invoices = FeeInvoice::query()
            ->with(['student:id,first_name,last_name,student_code', 'batch:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->batch_id))
            ->when($request->filled('month'), function ($q) use ($request) {
                $m = Carbon::createFromFormat('Y-m', $request->month);
                $q->whereYear('billing_period', $m->year)->whereMonth('billing_period', $m->month);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->search;
                $q->where(fn ($w) => $w->where('invoice_no', 'like', "%{$term}%")
                    ->orWhereHas('student', fn ($s) => $s->search($term)));
            })
            ->latest('billing_period')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.fees.invoices', [
            'invoices' => $invoices,
            'batches' => Batch::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function createInvoice()
    {
        return view('admin.fees.invoice-form', [
            'students' => Student::active()->approved()->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'student_code']),
            'structures' => FeeStructure::active()->with('batch:id,name')->orderBy('name')->get(),
        ]);
    }

    public function storeInvoice(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_structure_id' => 'nullable|exists:fee_structures,id',
            'billing_period' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $discount = (float) ($data['discount'] ?? 0);

        if ($discount > $data['amount']) {
            throw ValidationException::withMessages(['discount' => 'Discount cannot exceed the amount.']);
        }

        $student = Student::with('activeBatches')->findOrFail($data['student_id']);
        $period = Carbon::parse($data['billing_period'])->startOfMonth();

        $invoice = FeeInvoice::create([
            'invoice_no' => FeeInvoice::nextInvoiceNo(),
            'student_id' => $student->id,
            'batch_id' => $student->activeBatches->first()?->id,
            'fee_structure_id' => $data['fee_structure_id'] ?? null,
            'billing_period' => $period->toDateString(),
            'amount' => $data['amount'],
            'discount' => $discount,
            'late_fee' => 0,
            'total_amount' => $data['amount'] - $discount,
            'paid_amount' => 0,
            'issue_date' => now()->toDateString(),
            'due_date' => $data['due_date'],
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth('admin')->id(),
        ]);

        $invoice->syncStatus();

        return redirect()->route('admin.fees.invoices.show', $invoice)->with('success', 'Invoice raised.');
    }

    /**
     * Raise this month's tuition invoice for every active student in a batch,
     * skipping anyone already billed for the period.
     */
    public function generateMonthly(Request $request)
    {
        $data = $request->validate([
            'billing_period' => 'required|date',
            'batch_id' => 'nullable|exists:batches,id',
        ]);

        $period = Carbon::parse($data['billing_period'])->startOfMonth();
        $dueDay = (int) \App\Models\Setting::get('fee_due_day', 10);

        $batches = Batch::active()
            ->when($data['batch_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->with(['activeStudents:id', 'feeStructures' => fn ($q) => $q->where('type', 'tuition')->where('frequency', 'monthly')->where('status', 'active')])
            ->get();

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($batches, $period, $dueDay, &$created, &$skipped) {
            foreach ($batches as $batch) {
                $structure = $batch->feeStructures->first();

                if (! $structure) {
                    continue;
                }

                foreach ($batch->activeStudents as $student) {
                    $exists = FeeInvoice::where('student_id', $student->id)
                        ->where('fee_structure_id', $structure->id)
                        ->whereDate('billing_period', $period->toDateString())
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    $invoice = FeeInvoice::create([
                        'invoice_no' => FeeInvoice::nextInvoiceNo(),
                        'student_id' => $student->id,
                        'batch_id' => $batch->id,
                        'fee_structure_id' => $structure->id,
                        'billing_period' => $period->toDateString(),
                        'amount' => $structure->amount,
                        'discount' => 0,
                        'late_fee' => 0,
                        'total_amount' => $structure->amount,
                        'paid_amount' => 0,
                        'issue_date' => $period->toDateString(),
                        'due_date' => $period->copy()->addDays(max(0, $dueDay - 1))->toDateString(),
                        'status' => 'pending',
                        'created_by' => auth('admin')->id(),
                    ]);

                    $invoice->syncStatus();
                    $created++;
                }
            }
        });

        return back()->with(
            'success',
            "{$created} invoices generated for {$period->format('F Y')}."
            .($skipped > 0 ? " {$skipped} already existed and were skipped." : '')
        );
    }

    public function showInvoice(FeeInvoice $invoice)
    {
        $invoice->load(['student', 'batch', 'feeStructure', 'payments.receivedBy', 'reminders', 'createdBy']);

        return view('admin.fees.invoice-show', compact('invoice'));
    }

    public function destroyInvoice(FeeInvoice $invoice)
    {
        if ($invoice->payments()->exists()) {
            return back()->with('error', 'Cannot delete an invoice that has payments against it. Cancel it instead.');
        }

        $invoice->delete();

        return redirect()->route('admin.fees.invoices')->with('success', 'Invoice deleted.');
    }

    /** Record a payment against an invoice. */
    public function collect(Request $request, FeeInvoice $invoice)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|before_or_equal:today',
            'mode' => 'required|in:cash,upi,card,net_banking,cheque,bank_transfer',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'This invoice is cancelled.');
        }

        // Never let a payment exceed what is still owed.
        if ((float) $data['amount'] > (float) $invoice->balance_amount + 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds the outstanding balance of '.number_format($invoice->balance_amount, 2).'.',
            ]);
        }

        // Saving the payment triggers the model hook that re-syncs the invoice.
        $payment = FeePayment::create([
            'receipt_no' => FeePayment::nextReceiptNo(),
            'fee_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'mode' => $data['mode'],
            'reference_no' => $data['reference_no'] ?? null,
            'status' => 'completed',
            'notes' => $data['notes'] ?? null,
            'received_by' => auth('admin')->id(),
        ]);

        return redirect()
            ->route('admin.fees.receipt', $payment)
            ->with('success', "Payment recorded. Receipt {$payment->receipt_no}.");
    }

    /**
     * Collect a student's fee for a month in one step.
     *
     * If the month has not been billed yet the invoice is raised first, using
     * exactly the same rules as generateMonthly(), then the payment is written
     * through the normal FeePayment path so the invoice re-syncs itself.
     */
    public function collectForStudent(Request $request, Student $student)
    {
        $data = $request->validate([
            'billing_period' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|before_or_equal:today',
            'mode' => 'required|in:cash,upi,card,net_banking,cheque,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        $period = Carbon::parse($data['billing_period'])->startOfMonth();

        $payment = DB::transaction(function () use ($student, $period, $data) {
            $invoice = $this->invoiceFor($student, $period);

            if ($invoice->status === 'cancelled') {
                return null;
            }

            // Never take more than is owed.
            $amount = min((float) $data['amount'], (float) $invoice->balance_amount);

            if ($amount <= 0) {
                return null;
            }

            return FeePayment::create([
                'receipt_no' => FeePayment::nextReceiptNo(),
                'fee_invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'mode' => $data['mode'],
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'received_by' => auth('admin')->id(),
            ]);
        });

        if (! $payment) {
            return back()->with('error', 'Nothing left to collect for '.$period->format('F Y').'.');
        }

        return redirect()
            ->route('admin.fees.receipt', $payment)
            ->with('success', "Payment recorded for {$student->full_name}. Receipt {$payment->receipt_no}.");
    }

    /**
     * The student's invoice for a period, raised on the spot if billing has
     * not run yet. Mirrors generateMonthly(): batch tuition structure, the
     * configured due day, no late fee.
     */
    private function invoiceFor(Student $student, Carbon $period): FeeInvoice
    {
        $existing = $student->feeInvoices()
            ->whereDate('billing_period', $period->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        $batch = $student->activeBatches()->first();

        $structure = $batch
            ? $batch->feeStructures()
                ->where('type', 'tuition')->where('frequency', 'monthly')->where('status', 'active')
                ->first()
            : null;

        $amount = (float) ($structure->amount ?? $batch->monthly_fee ?? 0);

        $invoice = FeeInvoice::create([
            'invoice_no' => FeeInvoice::nextInvoiceNo(),
            'student_id' => $student->id,
            'batch_id' => $batch?->id,
            'fee_structure_id' => $structure?->id,
            'billing_period' => $period->toDateString(),
            'amount' => $amount,
            'discount' => 0,
            'late_fee' => 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'issue_date' => $period->toDateString(),
            'due_date' => FeeInvoice::dueDateFor($period)->toDateString(),
            'status' => 'pending',
            'created_by' => auth('admin')->id(),
        ]);

        $invoice->syncStatus();

        return $invoice->refresh();
    }

    public function receipt(FeePayment $payment)
    {
        $payment->load(['student', 'invoice.batch', 'receivedBy']);

        return view('admin.fees.receipt', compact('payment'));
    }

    /** Log a reminder for every student who still owes for the month. */
    public function remindAll(Request $request, FeeBook $book)
    {
        $data = $request->validate([
            'channel' => 'required|in:email,sms,whatsapp,call',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $month = $this->month($request->merge(['month' => $data['month'] ?? null]));

        // Only rows that have actually been billed can be reminded.
        $invoices = $book->pending($month)
            ->pluck('invoice')
            ->filter()
            ->values();

        $sent = $this->logReminders($invoices, $data['channel']);

        return back()->with(
            'success',
            $sent > 0
                ? "Reminder logged for {$sent} student(s) via ".ucfirst($data['channel']).'.'
                : 'No pending students to remind.'
        );
    }

    /** Log a reminder for a hand-picked set of students. */
    public function remindSelected(Request $request)
    {
        $data = $request->validate([
            'channel' => 'required|in:email,sms,whatsapp,call',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'integer',
        ], [
            'invoice_ids.required' => 'Select at least one student first.',
        ]);

        // Re-check outstanding on the server, so a stale page cannot remind a
        // student who has since paid.
        $invoices = FeeInvoice::query()
            ->with('student')
            ->whereIn('id', $data['invoice_ids'])
            ->outstanding()
            ->get();

        $sent = $this->logReminders($invoices, $data['channel']);

        return back()->with(
            'success',
            $sent > 0
                ? "Reminder logged for {$sent} student(s) via ".ucfirst($data['channel']).'.'
                : 'Those students have already paid — nothing to send.'
        );
    }

    /**
     * Write one reminder per invoice. Single source of truth for reminder
     * logging, shared by the per-row, selected and all paths.
     */
    private function logReminders($invoices, string $channel): int
    {
        $adminId = auth('admin')->id();
        $sent = 0;

        foreach ($invoices as $invoice) {
            $student = $invoice->student;

            if (! $student) {
                continue;
            }

            FeeReminder::create([
                'fee_invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'channel' => $channel,
                'message' => $this->reminderText($student, $invoice),
                'status' => 'sent',
                'sent_at' => now(),
                'sent_by' => $adminId,
            ]);

            $sent++;
        }

        return $sent;
    }

    /** The humble reminder text — same message the parent sees on WhatsApp. */
    private function reminderText(Student $student, FeeInvoice $invoice): string
    {
        // The invoice needs its student to build guardian/child names.
        $invoice->setRelation('student', $invoice->relationLoaded('student') ? $invoice->student : $student);

        return $invoice->reminderMessage();
    }

    public function remind(Request $request, FeeInvoice $invoice)
    {
        $data = $request->validate(['channel' => 'required|in:email,sms,whatsapp,call']);

        // Record the exact humble message the parent receives on WhatsApp.
        $invoice->loadMissing('student');

        FeeReminder::create([
            'fee_invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'channel' => $data['channel'],
            'message' => $invoice->reminderMessage(),
            'status' => 'sent',
            'sent_at' => now(),
            'sent_by' => auth('admin')->id(),
        ]);

        // A background tap from the WhatsApp button expects JSON, not a redirect.
        if ($request->expectsJson()) {
            return response()->json(['logged' => true]);
        }

        return back()->with('success', 'Reminder logged against this invoice.');
    }

    // ------------------------------------------------------------ Structures

    public function structures()
    {
        return view('admin.fees.structures', [
            'structures' => FeeStructure::with('batch:id,name')->orderBy('type')->orderBy('name')->get(),
            'batches' => Batch::orderBy('name')->get(['id', 'name']),
            'dueDay' => FeeInvoice::dueDay(),
            'graceDays' => FeeInvoice::graceDays(),
        ]);
    }

    /** The academy's one due date a month, plus how long before a fee is late. */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'fee_due_day' => 'required|integer|min:1|max:28',
            'fee_grace_days' => 'required|integer|min:0|max:30',
        ], [
            'fee_due_day.max' => 'Use a day between 1 and 28 so every month has it.',
        ]);

        Setting::put('fee_due_day', $data['fee_due_day'], 'finance');
        Setting::put('fee_grace_days', $data['fee_grace_days'], 'finance');

        return back()->with(
            'success',
            "Fees are now due on day {$data['fee_due_day']} of each month, "
            ."marked overdue after {$data['fee_grace_days']} grace day(s)."
        );
    }

    public function storeStructure(Request $request)
    {
        FeeStructure::create($this->validatedStructure($request));

        return back()->with('success', 'Fee structure created.');
    }

    public function updateStructure(Request $request, FeeStructure $structure)
    {
        $structure->update($this->validatedStructure($request));

        return back()->with('success', 'Fee structure updated.');
    }

    public function destroyStructure(FeeStructure $structure)
    {
        if ($structure->invoices()->exists()) {
            return back()->with('error', 'Cannot delete a structure that already has invoices. Deactivate it instead.');
        }

        $structure->delete();

        return back()->with('success', 'Fee structure deleted.');
    }

    private function validatedStructure(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'batch_id' => 'nullable|exists:batches,id',
            'type' => 'required|in:admission,tuition,kit,tournament,equipment,other',
            'frequency' => 'required|in:one_time,monthly,quarterly,half_yearly,annual',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
    }
}

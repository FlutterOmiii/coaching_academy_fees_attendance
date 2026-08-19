<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Setting;
use App\Support\WhatsApp;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Coach salary payments, kept inside the expense book: each payment is an
 * ordinary Expense (so every analytics page already counts it) tagged with
 * coach_id + salary_month so per-coach history stays queryable.
 *
 * The amount is always editable at pay time — the coach's monthly_salary is
 * only the pre-filled default, never a lock.
 */
class CoachSalaryController extends Controller
{
    /** Month overview: who is paid, who is pending, one-tap pay. */
    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        // Paid totals for the selected month, keyed by coach.
        $paid = Expense::salaries()
            ->whereYear('salary_month', $month->year)
            ->whereMonth('salary_month', $month->month)
            ->selectRaw('coach_id, SUM(amount) total, COUNT(*) payments, MAX(id) last_id')
            ->groupBy('coach_id')
            ->get()
            ->keyBy('coach_id');

        // Latest payment per coach this month, for the WhatsApp resend button.
        $lastPayments = Expense::with('coach')
            ->whereIn('id', $paid->pluck('last_id')->filter())
            ->get()
            ->keyBy('coach_id');

        $rows = Coach::active()->orderBy('first_name')->get()->map(function (Coach $coach) use ($paid, $lastPayments) {
            $p = $paid->get($coach->id);
            $last = $lastPayments->get($coach->id);

            return [
                'coach' => $coach,
                'paid_total' => (float) ($p->total ?? 0),
                'payments' => (int) ($p->payments ?? 0),
                'wa_link' => $last ? WhatsApp::link($coach->phone, $this->message($coach, $last)) : null,
            ];
        });

        $monthTotal = (float) $rows->sum('paid_total');
        $expected = (float) $rows->sum(fn ($r) => (float) $r['coach']->monthly_salary);
        $pendingRows = $rows->filter(fn ($r) => $r['paid_total'] < (float) $r['coach']->monthly_salary);

        return view('admin.expenses.salaries', [
            'month' => $month,
            'rows' => $rows,
            'currency' => Setting::get('currency_symbol', '₹'),
            'summary' => [
                'month_total' => $monthTotal,
                'expected' => $expected,
                'paid_count' => $rows->count() - $pendingRows->count(),
                'coach_count' => $rows->count(),
                'pending_amount' => max(0, (float) $pendingRows->sum(fn ($r) => (float) $r['coach']->monthly_salary - $r['paid_total'])),
                'all_time' => (float) Expense::salaries()->sum('amount'),
            ],
        ]);
    }

    /** Record a salary payment. Amount arrives from an editable field. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'coach_id' => 'required|exists:coaches,id',
            'amount' => 'required|numeric|min:0.01|max:99999999',
            'salary_month' => 'required|date_format:Y-m',
            'expense_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:cash,upi,card,net_banking,cheque,bank_transfer',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'update_default' => 'nullable|boolean',
        ]);

        $coach = Coach::findOrFail($data['coach_id']);
        $salaryMonth = Carbon::createFromFormat('Y-m', $data['salary_month'])->startOfMonth();

        $category = ExpenseCategory::firstOrCreate(
            ['name' => 'Coach Salary'],
            ['color' => '#805dca', 'status' => 'active', 'description' => 'Monthly salaries paid to coaches'],
        );

        $expense = Expense::create([
            'expense_category_id' => $category->id,
            'coach_id' => $coach->id,
            'salary_month' => $salaryMonth->toDateString(),
            'title' => 'Salary — '.$coach->full_name.' — '.$salaryMonth->format('M Y'),
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'],
            'vendor' => $coach->full_name,
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth('admin')->id(),
        ]);

        // Optionally make this amount the coach's new default salary.
        if ($request->boolean('update_default') && (float) $data['amount'] !== (float) $coach->monthly_salary) {
            $coach->update(['monthly_salary' => $data['amount']]);
        }

        $currency = Setting::get('currency_symbol', '₹');

        return redirect()
            ->route('admin.expenses.salaries', ['month' => $salaryMonth->format('Y-m')])
            ->with('success', 'Salary of '.$currency.number_format((float) $expense->amount).' recorded for '.$coach->full_name.'.')
            ->with('salary_wa', [
                'coach' => $coach->full_name,
                'link' => WhatsApp::link($coach->phone, $this->message($coach, $expense)),
            ]);
    }

    /** Full salary history for one coach. */
    public function history(Coach $coach)
    {
        $payments = Expense::salaries()
            ->where('coach_id', $coach->id)
            ->latest('salary_month')
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15);

        // WhatsApp resend link per payment.
        $waLinks = $payments->getCollection()
            ->mapWithKeys(fn (Expense $p) => [$p->id => WhatsApp::link($coach->phone, $this->message($coach, $p))]);

        return view('admin.expenses.salary-history', [
            'coach' => $coach,
            'payments' => $payments,
            'waLinks' => $waLinks,
            'currency' => Setting::get('currency_symbol', '₹'),
            'totalPaid' => (float) Expense::salaries()->where('coach_id', $coach->id)->sum('amount'),
            'yearPaid' => (float) Expense::salaries()->where('coach_id', $coach->id)
                ->whereYear('salary_month', now()->year)->sum('amount'),
        ]);
    }

    /** Change a coach's default monthly salary from the salaries screen. */
    public function updateDefault(Request $request, Coach $coach)
    {
        $data = $request->validate(['monthly_salary' => 'required|numeric|min:0|max:99999999']);

        $coach->update(['monthly_salary' => $data['monthly_salary']]);

        return back()->with('success', $coach->full_name."'s monthly salary updated.");
    }

    /** The confirmation the coach receives on WhatsApp. */
    private function message(Coach $coach, Expense $payment): string
    {
        $currency = Setting::get('currency_symbol', '₹');
        $academy = Setting::get('academy_name', 'Cricket Academy');

        $lines = [
            'Hi '.$coach->first_name.',',
            '',
            'Your salary for *'.$payment->salary_month?->format('F Y').'* has been paid. ✅',
            '',
            'Amount: '.$currency.number_format((float) $payment->amount, 2),
            'Paid on: '.$payment->expense_date?->format('d M Y'),
            'Mode: '.$payment->payment_method_label,
        ];

        if ($payment->reference_no) {
            $lines[] = 'Ref: '.$payment->reference_no;
        }

        $lines[] = '';
        $lines[] = 'Thank you for your dedication! 🏏';
        $lines[] = '— '.$academy;

        return implode("\n", $lines);
    }
}

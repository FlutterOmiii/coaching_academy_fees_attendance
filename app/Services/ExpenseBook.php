<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business-expense analytics: how much is spent, where it goes, and how that
 * sits against the fees coming in. Read-only aggregation over the expenses
 * ledger — the CRUD lives in the controller.
 */
class ExpenseBook
{
    /** Headline figures with month-on-month and income comparison. */
    public function summary(?Carbon $month = null): array
    {
        $month = ($month ?? Carbon::now())->copy()->startOfMonth();
        $lastMonth = $month->copy()->subMonthNoOverflow();

        $thisMonth = $this->totalFor($month);
        $prevMonth = $this->totalFor($lastMonth);

        $yearStart = $month->copy()->startOfYear();
        $thisYear = (float) Expense::whereBetween('expense_date', [$yearStart, $month->copy()->endOfMonth()])->sum('amount');

        // Income (fees collected) this month, for the net position.
        $incomeMonth = (float) FeePayment::completed()
            ->whereYear('payment_date', $month->year)
            ->whereMonth('payment_date', $month->month)
            ->sum('amount');

        $top = $this->categoryBreakdown($month)->first();

        // Average monthly spend across the months that actually have expenses.
        $activeMonths = (int) Expense::selectRaw("COUNT(DISTINCT DATE_FORMAT(expense_date, '%Y-%m')) m")->value('m');
        $allTime = (float) Expense::sum('amount');

        return [
            'month_total' => $thisMonth,
            'month_change' => $this->percentChange($thisMonth, $prevMonth),
            'year_total' => $thisYear,
            'all_time' => $allTime,
            'entries_this_month' => Expense::forMonth($month->year, $month->month)->count(),
            'avg_monthly' => $activeMonths > 0 ? round($allTime / $activeMonths, 2) : 0.0,
            'income_month' => $incomeMonth,
            'net_month' => $incomeMonth - $thisMonth,
            'top_category' => $top['name'] ?? null,
            'top_category_amount' => $top['amount'] ?? 0,
            'currency' => Setting::get('currency_symbol', '₹'),
        ];
    }

    /**
     * Spend per category for a month, biggest first — the "where does the
     * money go?" answer. Percentages are of that month's total.
     */
    public function categoryBreakdown(?Carbon $month = null): Collection
    {
        $month = ($month ?? Carbon::now())->copy()->startOfMonth();

        $rows = Expense::query()
            ->leftJoin('expense_categories as c', 'c.id', '=', 'expenses.expense_category_id')
            ->forMonth($month->year, $month->month)
            ->groupBy('c.id', 'c.name', 'c.color')
            ->selectRaw('c.id, COALESCE(c.name, "Uncategorised") name, COALESCE(c.color, "#888ea8") color,
                         SUM(expenses.amount) amount, COUNT(*) entries')
            ->orderByDesc('amount')
            ->get();

        $total = (float) $rows->sum('amount');

        return $rows->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'color' => $r->color,
            'amount' => (float) $r->amount,
            'entries' => (int) $r->entries,
            'percentage' => $total > 0 ? round(($r->amount / $total) * 100, 1) : 0,
        ]);
    }

    /** Total expenses per month over the last N months, for the trend chart. */
    public function monthlyTrend(int $months = 12): array
    {
        $rows = Expense::query()
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') ym, SUM(amount) total")
            ->where('expense_date', '>=', Carbon::now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonths($rows, $months);
    }

    /** Fees collected vs expenses per month — the professional profit view. */
    public function incomeVsExpense(int $months = 12): array
    {
        $expenses = Expense::query()
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') ym, SUM(amount) total")
            ->where('expense_date', '>=', Carbon::now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $income = FeePayment::completed()
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') ym, SUM(amount) total")
            ->where('payment_date', '>=', Carbon::now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $exp = $this->mapMonths($expenses, $months);
        $inc = $this->mapMonths($income, $months);

        $net = array_map(fn ($i, $e) => round($i - $e, 2), $inc['data'], $exp['data']);

        return [
            'labels' => $exp['labels'],
            'income' => $inc['data'],
            'expense' => $exp['data'],
            'net' => $net,
        ];
    }

    /** Split by how it was paid. */
    public function paymentBreakdown(?Carbon $month = null): array
    {
        $month = ($month ?? Carbon::now())->copy()->startOfMonth();

        $rows = Expense::forMonth($month->year, $month->month)
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount) total')
            ->pluck('total', 'payment_method');

        $labels = [];
        $data = [];
        foreach (Expense::PAYMENT_METHODS as $key => $label) {
            if (($rows[$key] ?? 0) > 0) {
                $labels[] = $label;
                $data[] = round((float) $rows[$key], 2);
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** Who the academy pays the most, over the last 3 months. */
    public function topVendors(int $limit = 6): Collection
    {
        return Expense::query()
            ->whereNotNull('vendor')
            ->where('vendor', '!=', '')
            ->where('expense_date', '>=', Carbon::now()->subMonths(3)->startOfMonth())
            ->groupBy('vendor')
            ->selectRaw('vendor, SUM(amount) total, COUNT(*) entries')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['vendor' => $r->vendor, 'total' => (float) $r->total, 'entries' => (int) $r->entries]);
    }

    public function recent(int $limit = 8): Collection
    {
        return Expense::with('category:id,name,color')
            ->latest('expense_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    // ------------------------------------------------------------- Internals

    private function totalFor(Carbon $month): float
    {
        return (float) Expense::forMonth($month->year, $month->month)->sum('amount');
    }

    private function percentChange(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function mapMonths(Collection $rows, int $count): array
    {
        $labels = [];
        $data = [];

        foreach (range($count - 1, 0) as $i) {
            $m = Carbon::now()->subMonthsNoOverflow($i)->startOfMonth();
            $labels[] = $m->format('M y');
            $data[] = round((float) ($rows[$m->format('Y-m')] ?? 0), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}

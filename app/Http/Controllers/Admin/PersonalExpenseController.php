<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * The owner's private expense ledger with its own analytics page. Every action
 * is scoped to the signed-in admin's own rows, and the routes are gated by the
 * owner-only `personal.manage` ability — this never touches the business books.
 */
class PersonalExpenseController extends Controller
{
    public function index(Request $request)
    {
        $adminId = auth('admin')->id();
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        // The owner's own category list (seeded with sensible defaults once).
        $categoryList = PersonalExpenseCategory::forOwner($adminId);
        $iconMap = $categoryList->pluck('icon', 'name');

        $entries = PersonalExpense::ownedBy($adminId)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('month'), fn ($q) => $q->forMonth($month->year, $month->month))
            ->latest('spent_on')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // An entry being edited, pre-filled into the form.
        $editing = $request->filled('edit')
            ? PersonalExpense::ownedBy($adminId)->find($request->edit)
            : null;

        return view('admin.personal.index', [
            'month' => $month,
            'entries' => $entries,
            'editing' => $editing,
            'categoryList' => $categoryList,
            'iconMap' => $iconMap,
            'summary' => $this->summary($adminId, $month, $iconMap),
            'trend' => $this->monthlyTrend($adminId, 12),
            'categories' => $this->categoryBreakdown($adminId, $month, $iconMap),
        ]);
    }

    public function store(Request $request)
    {
        PersonalExpense::create($this->validated($request) + ['admin_id' => auth('admin')->id()]);

        return redirect()->route('admin.personal.index')->with('success', 'Personal expense added.');
    }

    public function update(Request $request, PersonalExpense $personalExpense)
    {
        abort_unless($personalExpense->admin_id === auth('admin')->id(), 403);

        $personalExpense->update($this->validated($request));

        return redirect()->route('admin.personal.index')->with('success', 'Personal expense updated.');
    }

    public function destroy(PersonalExpense $personalExpense)
    {
        // A person can only delete their own personal entries.
        abort_unless($personalExpense->admin_id === auth('admin')->id(), 403);

        $personalExpense->delete();

        return back()->with('success', 'Personal expense removed.');
    }

    /**
     * Inline "+ Add category" from the expense form's dropdown. Creates the
     * category for the signed-in owner (or reuses an existing one) and returns
     * it as JSON for the searchable-select to slot in.
     */
    public function quickCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:50']);
        $adminId = auth('admin')->id();

        $category = PersonalExpenseCategory::firstOrCreate(
            ['admin_id' => $adminId, 'name' => $data['name']],
            ['icon' => '📌'],
        );

        // id is the category name (what personal_expenses.category stores);
        // name carries the emoji so the option shows nicely in the dropdown.
        return response()->json([
            'id' => $category->name,
            'name' => trim($category->icon.' '.$category->name),
        ]);
    }

    // ------------------------------------------------------------- Analytics

    private function summary(int $adminId, Carbon $month, $iconMap): array
    {
        $base = fn () => PersonalExpense::ownedBy($adminId);

        $monthTotal = (float) $base()->forMonth($month->year, $month->month)->sum('amount');
        $prev = $month->copy()->subMonthNoOverflow();
        $prevTotal = (float) $base()->forMonth($prev->year, $prev->month)->sum('amount');

        $allTime = (float) $base()->sum('amount');
        $activeMonths = (int) $base()->selectRaw("COUNT(DISTINCT DATE_FORMAT(spent_on, '%Y-%m')) m")->value('m');

        $top = $this->categoryBreakdown($adminId, $month, $iconMap)->first();

        return [
            'month_total' => $monthTotal,
            'month_change' => $prevTotal > 0 ? round((($monthTotal - $prevTotal) / $prevTotal) * 100, 1) : ($monthTotal > 0 ? 100.0 : null),
            'year_total' => (float) $base()->whereYear('spent_on', $month->year)->sum('amount'),
            'all_time' => $allTime,
            'avg_monthly' => $activeMonths > 0 ? round($allTime / $activeMonths, 2) : 0.0,
            'entries_month' => $base()->forMonth($month->year, $month->month)->count(),
            'top_category' => $top['category'] ?? null,
            'top_category_icon' => $top['icon'] ?? '',
            'top_category_amount' => $top['total'] ?? 0,
            'currency' => Setting::get('currency_symbol', '₹'),
        ];
    }

    private function monthlyTrend(int $adminId, int $months): array
    {
        $rows = PersonalExpense::ownedBy($adminId)
            ->selectRaw("DATE_FORMAT(spent_on, '%Y-%m') ym, SUM(amount) total")
            ->where('spent_on', '>=', Carbon::now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $data = [];
        foreach (range($months - 1, 0) as $i) {
            $m = Carbon::now()->subMonthsNoOverflow($i)->startOfMonth();
            $labels[] = $m->format('M y');
            $data[] = round((float) ($rows[$m->format('Y-m')] ?? 0), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function categoryBreakdown(int $adminId, Carbon $month, $iconMap)
    {
        $rows = PersonalExpense::ownedBy($adminId)
            ->forMonth($month->year, $month->month)
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) total, COUNT(*) entries')
            ->orderByDesc('total')
            ->get();

        $total = (float) $rows->sum('total');

        return $rows->map(fn ($r) => [
            'category' => $r->category,
            'icon' => $iconMap[$r->category] ?? '📌',
            'total' => (float) $r->total,
            'entries' => (int) $r->entries,
            'percentage' => $total > 0 ? round(($r->total / $total) * 100, 1) : 0,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:99999999',
            'spent_on' => 'required|date|before_or_equal:today',
            'category' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);
    }
}

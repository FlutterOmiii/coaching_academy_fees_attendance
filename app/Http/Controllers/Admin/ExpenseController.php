<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseBook;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /** Analytics dashboard: where the money goes. */
    public function index(Request $request, ExpenseBook $book)
    {
        $month = $this->month($request);

        return view('admin.expenses.index', [
            'month' => $month,
            'summary' => $book->summary($month),
            'categories' => $book->categoryBreakdown($month),
            'trend' => $book->monthlyTrend(12),
            'incomeVsExpense' => $book->incomeVsExpense(12),
            'payment' => $book->paymentBreakdown($month),
            'topVendors' => $book->topVendors(6),
            'recent' => $book->recent(8),
        ]);
    }

    /** Full searchable ledger with CRUD. */
    public function list(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : null;

        $expenses = Expense::query()
            ->with('category:id,name,color', 'createdBy:id,name')
            ->search($request->string('search')->toString())
            ->when($request->filled('category_id'), fn ($q) => $q->where('expense_category_id', $request->category_id))
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->method))
            ->when($month, fn ($q) => $q->forMonth($month->year, $month->month))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $currency = \App\Models\Setting::get('currency_symbol', '₹');
        $filteredTotal = (float) Expense::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('category_id'), fn ($q) => $q->where('expense_category_id', $request->category_id))
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->method))
            ->when($month, fn ($q) => $q->forMonth($month->year, $month->month))
            ->sum('amount');

        return view('admin.expenses.list', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::active()->orderBy('name')->get(),
            'currency' => $currency,
            'filteredTotal' => $filteredTotal,
        ]);
    }

    public function create()
    {
        return view('admin.expenses.form', [
            'expense' => new Expense(['expense_date' => now()->toDateString(), 'payment_method' => 'cash']),
            'categories' => ExpenseCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $expense = Expense::create($this->validated($request) + ['created_by' => auth('admin')->id()]);

        return redirect()->route('admin.expenses.list')
            ->with('success', 'Expense of '.$this->currency().number_format($expense->amount).' recorded.');
    }

    public function edit(Expense $expense)
    {
        return view('admin.expenses.form', [
            'expense' => $expense,
            'categories' => ExpenseCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return redirect()->route('admin.expenses.list')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Expense deleted.');
    }

    // ------------------------------------------------------------ Categories

    public function categories()
    {
        return view('admin.expenses.categories', [
            'categories' => ExpenseCategory::withCount('expenses')
                ->withSum('expenses', 'amount')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        ExpenseCategory::create($this->validatedCategory($request));

        return back()->with('success', 'Category added.');
    }

    /**
     * Create a category inline from the expense form's dropdown, returning
     * JSON for the searchable-select to insert and pick. A colour is assigned
     * automatically so the flow stays one field.
     */
    public function quickCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);

        // Reuse an existing category of the same name rather than duplicating.
        $category = ExpenseCategory::firstOrCreate(
            ['name' => $data['name']],
            ['color' => $this->nextColor(), 'status' => 'active']
        );

        return response()->json(['id' => $category->id, 'name' => $category->name]);
    }

    /** Pick the next unused palette colour so new categories look distinct. */
    private function nextColor(): string
    {
        $palette = ['#4361ee', '#00ab55', '#e2a03f', '#e7515a', '#2196f3', '#805dca', '#f4772e', '#0ea5e9', '#e91e63', '#3b3f5c'];
        $used = ExpenseCategory::pluck('color')->all();
        $free = array_values(array_diff($palette, $used));

        return $free[0] ?? $palette[ExpenseCategory::count() % count($palette)];
    }

    public function updateCategory(Request $request, ExpenseCategory $category)
    {
        $category->update($this->validatedCategory($request));

        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            return back()->with('error', 'Cannot delete a category that still has expenses. Deactivate it instead.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    // ------------------------------------------------------------- Internals

    private function validated(Request $request): array
    {
        return $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:99999999',
            'expense_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:cash,upi,card,net_banking,cheque,bank_transfer',
            'vendor' => 'nullable|string|max:255',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);
    }

    private function validatedCategory(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);
    }

    private function month(Request $request): Carbon
    {
        return $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();
    }

    private function currency(): string
    {
        return \App\Models\Setting::get('currency_symbol', '₹');
    }
}

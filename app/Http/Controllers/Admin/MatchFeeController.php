<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\FeeMatch;
use App\Models\MatchFee;
use App\Models\Setting;
use App\Models\Student;
use App\Support\WhatsApp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Match Fees: create a match, pick the participating students, and collect a
 * one-off fee from each. Lives inside the Fees module (same abilities, same
 * design) but runs beside the monthly invoice pipeline, never through it, so
 * monthly fee analytics are untouched.
 */
class MatchFeeController extends Controller
{
    /** Summary cards + the match-wise history list. */
    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : null;

        $matches = FeeMatch::query()
            ->with('fees:id,fee_match_id,amount,status')
            ->search($request->string('search')->toString())
            ->when($month, fn ($q) => $q->whereYear('match_date', $month->year)->whereMonth('match_date', $month->month))
            ->latest('match_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $all = MatchFee::query();

        return view('admin.fees.matches.index', [
            'matches' => $matches,
            'currency' => Setting::get('currency_symbol', '₹'),
            'summary' => [
                'matches' => FeeMatch::count(),
                'billed' => (float) $all->clone()->sum('amount'),
                'collected' => (float) $all->clone()->paid()->sum('amount'),
                'pending' => (float) $all->clone()->pending()->sum('amount'),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.fees.matches.form', [
            'match' => new FeeMatch(['match_date' => now()->toDateString()]),
            'students' => $this->selectableStudents(),
            'batches' => Batch::active()->orderBy('name')->get(['id', 'name']),
            'selectedIds' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $match = DB::transaction(function () use ($data) {
            $match = FeeMatch::create([
                ...collect($data)->except('student_ids')->all(),
                'created_by' => auth('admin')->id(),
            ]);

            $this->attachStudents($match, $data['student_ids']);

            return $match;
        });

        return redirect()
            ->route('admin.fees.matches.show', $match)
            ->with('success', "Match created — ".count($data['student_ids'])." student(s) selected for {$match->title}.");
    }

    /** Match → selected students → payment status, with one-tap collection. */
    public function show(FeeMatch $match)
    {
        $match->load(['fees.student:id,first_name,last_name,student_code,guardian_name,guardian_phone', 'fees.collectedBy:id,name']);

        $fees = $match->fees->sortBy(fn (MatchFee $f) => $f->student?->full_name)->values();

        return view('admin.fees.matches.show', [
            'match' => $match,
            'fees' => $fees,
            'currency' => Setting::get('currency_symbol', '₹'),
            // Students who can still be added to this match.
            'addable' => $this->selectableStudents()->reject(
                fn ($s) => $match->fees->pluck('student_id')->contains($s->id)
            )->values(),
            'waLinks' => $fees->mapWithKeys(fn (MatchFee $f) => [
                $f->id => $f->status === 'pending' ? $this->reminderLink($f) : null,
            ]),
        ]);
    }

    public function edit(FeeMatch $match)
    {
        return view('admin.fees.matches.form', [
            'match' => $match,
            'students' => collect(),
            'batches' => collect(),
            'selectedIds' => [],
        ]);
    }

    public function update(Request $request, FeeMatch $match)
    {
        $data = $this->validated($request, editing: true);
        $oldFee = (float) $match->fee_amount;

        $match->update($data);

        // A changed default fee follows through to students who haven't paid;
        // already-paid records keep the amount that was actually collected.
        if ((float) $match->fee_amount !== $oldFee) {
            $match->fees()->pending()->update(['amount' => $match->fee_amount]);
        }

        return redirect()->route('admin.fees.matches.show', $match)->with('success', 'Match updated.');
    }

    public function destroy(FeeMatch $match)
    {
        $match->delete();

        return redirect()->route('admin.fees.matches.index')->with('success', 'Match deleted.');
    }

    // ------------------------------------------------------------- Students

    /** Add more students to an existing match. */
    public function addStudents(Request $request, FeeMatch $match)
    {
        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $added = $this->attachStudents($match, $data['student_ids']);

        return back()->with('success', $added.' student(s) added to '.$match->title.'.');
    }

    /** Remove a student from the match (their fee record goes with them). */
    public function destroyRecord(MatchFee $matchFee)
    {
        $matchFee->delete();

        return back()->with('success', 'Student removed from the match.');
    }

    // ------------------------------------------------------------ Collection

    /** Mark a student's match fee as paid. */
    public function pay(Request $request, MatchFee $matchFee)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:9999999',
            'payment_date' => 'required|date|before_or_equal:today',
            'mode' => 'required|in:'.implode(',', array_keys(MatchFee::MODES)),
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $matchFee->update($data + [
            'status' => 'paid',
            'receipt_no' => $matchFee->receipt_no ?? MatchFee::nextReceiptNo(),
            'collected_by' => auth('admin')->id(),
        ]);

        $matchFee->load('match', 'student');

        return back()
            ->with('success', 'Match fee collected from '.$matchFee->student?->full_name.' — receipt '.$matchFee->receipt_no.'.')
            ->with('receipt_wa', [
                'name' => $matchFee->student?->full_name,
                'link' => WhatsApp::link($matchFee->student?->guardian_phone, $matchFee->receiptMessage()),
                'receipt_url' => route('admin.fees.matches.receipt', $matchFee),
            ]);
    }

    /** Revert a record to pending (wrong entry, bounced payment, ...). */
    public function markPending(MatchFee $matchFee)
    {
        $matchFee->update([
            'status' => 'pending',
            'payment_date' => null,
            'mode' => null,
            'reference_no' => null,
            'receipt_no' => null,
            'collected_by' => null,
        ]);

        return back()->with('success', 'Marked as pending again.');
    }

    // ---------------------------------------------------------------- Records

    /** Flat, filterable list of every match-fee record. */
    public function records(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : null;

        $records = MatchFee::query()
            ->with(['match:id,title,match_date', 'student:id,first_name,last_name,student_code,guardian_name,guardian_phone'])
            ->when($request->filled('fee_match_id'), fn ($q) => $q->where('fee_match_id', $request->fee_match_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->whereHas(
                'student', fn ($s) => $s->search($request->string('search')->toString())
            ))
            ->when($month, fn ($q) => $q->whereHas(
                'match', fn ($m) => $m->whereYear('match_date', $month->year)->whereMonth('match_date', $month->month)
            ))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $waLinks = $records->getCollection()->mapWithKeys(fn (MatchFee $f) => [
            $f->id => $f->status === 'pending' ? $this->reminderLink($f) : null,
        ]);

        return view('admin.fees.matches.records', [
            'records' => $records,
            'waLinks' => $waLinks,
            'matches' => FeeMatch::latest('match_date')->get(['id', 'title', 'match_date']),
            'currency' => Setting::get('currency_symbol', '₹'),
        ]);
    }

    /** Printable receipt, same visual system as monthly fee receipts. */
    public function receipt(MatchFee $matchFee)
    {
        abort_unless($matchFee->status === 'paid', 404);

        $matchFee->load('match', 'student', 'collectedBy');

        return view('admin.fees.matches.receipt', [
            'record' => $matchFee,
            'currency' => Setting::get('currency_symbol', '₹'),
            'waLink' => WhatsApp::link($matchFee->student?->guardian_phone, $matchFee->receiptMessage()),
        ]);
    }

    // ------------------------------------------------------------- Internals

    /** Active, approved students with their batch, for the pickers. */
    private function selectableStudents()
    {
        return Student::active()
            ->where('admission_status', 'approved')
            ->with('activeBatches:id,name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'student_code']);
    }

    /** Create pending fee records, skipping students already on the match. */
    private function attachStudents(FeeMatch $match, array $studentIds): int
    {
        $existing = $match->fees()->pluck('student_id')->all();
        $fresh = array_diff(array_unique(array_map('intval', $studentIds)), $existing);

        foreach ($fresh as $studentId) {
            $match->fees()->create([
                'student_id' => $studentId,
                'amount' => $match->fee_amount,
                'status' => 'pending',
            ]);
        }

        return count($fresh);
    }

    private function validated(Request $request, bool $editing = false): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'match_date' => 'required|date',
            'venue' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'fee_amount' => 'required|numeric|min:0.01|max:9999999',
        ] + ($editing ? [] : [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]));
    }

    /** Humble WhatsApp reminder for a pending match fee, house format. */
    private function reminderLink(MatchFee $fee): ?string
    {
        $student = $fee->student;
        $match = $fee->match ?? $fee->load('match')->match;

        if (! $student || ! $match) {
            return null;
        }

        $academy = Setting::get('academy_name', 'our academy');
        $currency = Setting::get('currency_symbol', '₹');
        $guardian = $student->guardian_name ?: 'Parent';
        $amount = $currency.number_format((float) $fee->amount);

        $message = "Dear {$guardian},\n\n"
            ."Warm greetings from *{$academy}*. "
            ."This is a gentle reminder that the match fee of *{$amount}* for "
            ."*{$student->full_name}* for *{$match->title}* "
            ."({$match->match_date->format('d M Y')}) is still remaining. "
            ."We kindly request you to please pay it at your earliest convenience.\n\n"
            ."If you have already made the payment, please share the screenshot. "
            ."Thank you for your continued support.\n\n"
            ."*Warm regards,*\n"
            ."*{$academy}*";

        return WhatsApp::link($student->guardian_phone, $message);
    }
}

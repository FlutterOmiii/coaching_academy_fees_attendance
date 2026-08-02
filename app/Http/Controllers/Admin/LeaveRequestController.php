<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\LeaveRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $leaves = LeaveRequest::query()
            ->with('leavable', 'approvedBy')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->input('who') === 'students', fn ($q) => $q->where('leavable_type', Student::class))
            ->when($request->input('who') === 'coaches', fn ($q) => $q->where('leavable_type', Coach::class))
            ->latest('from_date')
            ->paginate(15)
            ->withQueryString();

        return view('admin.leaves.index', [
            'leaves' => $leaves,
            'counts' => [
                'pending' => LeaveRequest::pending()->count(),
                'approved' => LeaveRequest::approved()->count(),
                'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.leaves.create', [
            'students' => Student::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'student_code']),
            'coaches' => Coach::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'coach_code']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // One combined picker submits "student:93" or "coach:4".
            'person' => ['required', 'string', 'regex:/^(student|coach):\d+$/'],
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'type' => 'required|in:sick,personal,travel,exam,other',
            'reason' => 'nullable|string|max:1000',
        ], [
            'person.regex' => 'Please select a student or coach.',
        ]);

        [$type, $id] = explode(':', $data['person']);
        $class = $type === 'student' ? Student::class : Coach::class;

        if (! $class::whereKey($id)->exists()) {
            throw ValidationException::withMessages(['person' => 'That person no longer exists.']);
        }

        LeaveRequest::create([
            'leavable_type' => $class,
            'leavable_id' => $id,
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'type' => $data['type'],
            // Nullable rules are absent from validated() when not submitted.
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.leaves.index')->with('success', 'Leave request submitted.');
    }

    public function decide(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $leave->update([
            'status' => $data['decision'],
            'approved_by' => auth('admin')->id(),
            'approved_at' => now(),
            'rejection_reason' => $data['decision'] === 'rejected'
                ? ($data['rejection_reason'] ?? null)
                : null,
        ]);

        return back()->with('success', "Leave request {$data['decision']}.");
    }

    public function destroy(LeaveRequest $leave)
    {
        $leave->delete();

        return back()->with('success', 'Leave request deleted.');
    }
}

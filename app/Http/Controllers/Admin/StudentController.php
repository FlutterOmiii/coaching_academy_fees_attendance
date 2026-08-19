<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchTransfer;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Support\WhatsApp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::query()
            ->with(['activeBatches:id,name'])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('admission_status'), fn ($q) => $q->where('admission_status', $request->admission_status))
            ->when($request->filled('playing_role'), fn ($q) => $q->where('playing_role', $request->playing_role))
            ->when($request->filled('batch_id'), fn ($q) => $q->whereHas(
                'batches',
                fn ($b) => $b->where('batches.id', $request->batch_id)->where('batch_student.status', 'active')
            ))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'batches' => Batch::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('admin.students.form', [
            'student' => new Student(['admission_date' => now()->toDateString()]),
            'batches' => Batch::active()->orderBy('name')->get(['id', 'name']),
            'nextCode' => Student::nextCode(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $batchId = $request->input('batch_id');

        $student = DB::transaction(function () use ($request, $data, $batchId) {
            $data['student_code'] = Student::nextCode();

            if ($request->hasFile('photo')) {
                $data['photo'] = StorageHelper::upload($request->file('photo'), 'students');
            }

            $student = Student::create($data);

            if ($batchId) {
                $student->batches()->attach($batchId, [
                    'joined_on' => $data['admission_date'],
                    'status' => 'active',
                ]);
            }

            return $student;
        });

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', "Student {$student->student_code} registered successfully.")
            ->with('admission_wa', [
                'guardian' => $student->guardian_name,
                'link' => $this->admissionWhatsAppLink($student),
            ]);
    }

    // ----------------------------------------------------------- Birthdays

    /**
     * Month-by-month birthday list of every student (admin-only info page).
     * Includes inactive students, dimmed, so no birthday is ever missed.
     */
    public function birthdays(Request $request)
    {
        $month = min(12, max(1, (int) $request->input('month', now()->month)));
        $year = now()->year;
        $academy = Setting::get('academy_name', 'Cricket Academy');

        $rows = Student::query()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $month)
            ->with('activeBatches:id,name')
            ->orderByRaw('DAY(date_of_birth)')
            ->orderBy('first_name')
            ->get()
            ->map(function (Student $s) use ($month, $year, $academy) {
                // This year's occurrence; 29 Feb clamps to 28 in non-leap years.
                $bday = Carbon::create($year, $month, min(
                    $s->date_of_birth->day,
                    Carbon::create($year, $month)->daysInMonth
                ));

                $wish = implode("\n", [
                    'Dear '.$s->guardian_name.',',
                    '',
                    '🎂 *'.$academy.'* wishes '.$s->full_name.' a very *Happy Birthday!*',
                    'May the year ahead be filled with runs, wickets and wonderful memories on the pitch. 🏏',
                    '',
                    'Warm wishes,',
                    '— '.$academy,
                ]);

                return [
                    'student' => $s,
                    'date' => $bday,
                    'turning' => $year - $s->date_of_birth->year,
                    'is_today' => $bday->isToday(),
                    'is_tomorrow' => $bday->isTomorrow(),
                    'wa_link' => WhatsApp::link($s->guardian_phone, $wish),
                ];
            });

        // Per-month totals for the tab strip.
        $counts = Student::whereNotNull('date_of_birth')
            ->selectRaw('MONTH(date_of_birth) m, COUNT(*) c')
            ->groupBy('m')
            ->pluck('c', 'm');

        return view('admin.students.birthdays', [
            'month' => $month,
            'rows' => $rows,
            'counts' => $counts,
            'missingDob' => Student::whereNull('date_of_birth')->count(),
        ]);
    }

    // ------------------------------------------------------- Admission form

    /** The printable admission document, inside the admin panel. */
    public function admissionForm(Student $student)
    {
        return view('admin.students.admission-form', [
            'student' => $student->load('activeBatches.coach'),
            'public' => false,
            'waLink' => $this->admissionWhatsAppLink($student),
        ]);
    }

    /** Guardian view of the same document, reached via the signed WhatsApp link. */
    public function admissionFormPublic(Student $student)
    {
        return view('admin.students.admission-form', [
            'student' => $student->load('activeBatches.coach'),
            'public' => true,
            'waLink' => null,
        ]);
    }

    /** WhatsApp welcome message to the guardian, carrying the signed form link. */
    private function admissionWhatsAppLink(Student $student): ?string
    {
        $academy = Setting::get('academy_name', 'Cricket Academy');
        $batch = $student->activeBatches()->first();

        $lines = [
            'Dear '.$student->guardian_name.',',
            '',
            'Welcome to *'.$academy.'*! 🏏',
            'We are delighted to confirm the admission of *'.$student->full_name.'*.',
            '',
            'Admission No: '.$student->student_code,
            'Date of Admission: '.$student->admission_date?->format('d M Y'),
        ];

        if ($batch) {
            $lines[] = 'Batch: '.$batch->name;
        }

        $lines[] = '';
        $lines[] = 'You can view and save the official Admission Form here:';
        $lines[] = URL::signedRoute('admission.view', ['student' => $student->id]);
        $lines[] = '';
        $lines[] = 'We look forward to a wonderful cricketing journey together! 🙏';
        $lines[] = '— '.$academy;

        return WhatsApp::link($student->guardian_phone, implode("\n", $lines));
    }

    public function show(Student $student)
    {
        $student->load([
            'activeBatches.coach',
            'documents.uploadedBy',
            'transfers.fromBatch',
            'transfers.toBatch',
            'assessments.coach',
            'teams',
        ]);

        // Only fetch money for roles allowed to see it; a coach's response
        // carries no fee data at all.
        $invoices = auth('admin')->user()?->hasAbility('finance.view')
            ? $student->feeInvoices()->latest('billing_period')->limit(12)->get()
            : collect();

        $attendance = $student->attendances();
        $attendanceStats = [
            'total' => (clone $attendance)->count(),
            'present' => (clone $attendance)->present()->count(),
        ];
        $attendanceStats['percentage'] = $attendanceStats['total'] > 0
            ? round(($attendanceStats['present'] / $attendanceStats['total']) * 100, 1)
            : 0;

        // Career batting/bowling totals across every match played.
        $career = $student->performances()
            ->selectRaw('COUNT(*) matches, SUM(runs_scored) runs, SUM(balls_faced) balls,
                         SUM(wickets) wickets, SUM(catches) catches, MAX(runs_scored) best')
            ->first();

        return view('admin.students.show', [
            'student' => $student,
            'invoices' => $invoices,
            'attendanceStats' => $attendanceStats,
            'career' => $career,
            'batches' => Batch::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Student $student)
    {
        return view('admin.students.form', [
            'student' => $student,
            'batches' => Batch::active()->orderBy('name')->get(['id', 'name']),
            'nextCode' => $student->student_code,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validated($request, $student);

        if ($request->hasFile('photo')) {
            StorageHelper::delete($student->photo);
            $data['photo'] = StorageHelper::upload($request->file('photo'), 'students');
        }

        $student->update($data);

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', 'Student details updated.');
    }

    public function destroy(Student $student)
    {
        $student->delete(); // soft delete — history stays intact

        return redirect()
            ->route('admin.students.index')
            ->with('success', "Student {$student->student_code} removed.");
    }

    public function toggleStatus(Student $student)
    {
        $student->update(['status' => $student->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', "Student marked {$student->status}.");
    }

    public function updateAdmission(Request $request, Student $student)
    {
        $request->validate(['admission_status' => 'required|in:pending,approved,rejected']);

        $student->update([
            'admission_status' => $request->admission_status,
            // Approving a student activates them; rejecting deactivates.
            'status' => $request->admission_status === 'approved' ? 'active' : 'inactive',
        ]);

        return back()->with('success', "Admission {$request->admission_status}.");
    }

    public function storeDocument(Request $request, Student $student)
    {
        $request->validate([
            'type' => 'required|in:'.implode(',', array_keys(StudentDocument::TYPES)),
            'title' => 'required|string|max:255',
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $file = $request->file('file');

        $student->documents()->create([
            'type' => $request->type,
            'title' => $request->title,
            'file_path' => StorageHelper::upload($file, 'documents'),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function destroyDocument(Student $student, StudentDocument $document)
    {
        abort_unless($document->student_id === $student->id, 404);

        StorageHelper::delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    /** Move a student to another batch and record the transfer. */
    public function transfer(Request $request, Student $student)
    {
        $request->validate([
            'to_batch_id' => 'required|exists:batches,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $current = $student->activeBatches()->first();

        if ($current && (int) $current->id === (int) $request->to_batch_id) {
            return back()->with('error', 'Student is already in that batch.');
        }

        DB::transaction(function () use ($request, $student, $current) {
            if ($current) {
                $student->batches()->updateExistingPivot($current->id, [
                    'status' => 'transferred',
                    'left_on' => now()->toDateString(),
                ]);
            }

            $student->batches()->attach($request->to_batch_id, [
                'joined_on' => now()->toDateString(),
                'status' => 'active',
            ]);

            BatchTransfer::create([
                'student_id' => $student->id,
                'from_batch_id' => $current?->id,
                'to_batch_id' => $request->to_batch_id,
                'transferred_on' => now()->toDateString(),
                'reason' => $request->reason,
                'transferred_by' => auth('admin')->id(),
            ]);
        });

        return back()->with('success', 'Student transferred to the new batch.');
    }

    /** CSV export of the current filter selection. */
    public function export(Request $request)
    {
        $students = Student::query()
            ->with('activeBatches:id,name')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('student_code')
            ->get();

        $columns = ['Code', 'Name', 'Age', 'Gender', 'Role', 'Batch', 'Guardian', 'Phone', 'Admission', 'Status'];

        return response()->streamDownload(function () use ($students, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            foreach ($students as $s) {
                fputcsv($out, [
                    $s->student_code,
                    $s->full_name,
                    $s->age,
                    ucfirst($s->gender),
                    $s->playing_role_label,
                    $s->activeBatches->pluck('name')->implode(', '),
                    $s->guardian_name,
                    $s->guardian_phone,
                    $s->admission_date?->format('Y-m-d'),
                    $s->status,
                ]);
            }

            fclose($out);
        }, 'students-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|string|max:5',
            'photo' => 'nullable|image|max:2048',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'school_name' => 'nullable|string|max:255',
            'guardian_name' => 'required|string|max:255',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_relation' => 'nullable|string|max:50',
            'playing_role' => 'required|in:'.implode(',', array_keys(Student::PLAYING_ROLES)),
            'batting_style' => 'nullable|in:right_hand,left_hand',
            'bowling_style' => 'nullable|in:right_arm_fast,right_arm_medium,right_arm_off_spin,right_arm_leg_spin,left_arm_fast,left_arm_medium,left_arm_orthodox,left_arm_chinaman,none',
            'admission_date' => 'required|date',
            'admission_status' => 'required|in:pending,approved,rejected',
            'status' => 'required|in:active,inactive',
            'medical_notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // One Full Name field on the form → first/last columns in the DB.
        $data = $this->splitFullName($data['full_name']) + $data;
        unset($data['full_name']);

        return $data;
    }
}

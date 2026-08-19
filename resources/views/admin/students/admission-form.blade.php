<!DOCTYPE html>
<html lang="en">
@php
    $academy = \App\Models\Setting::get('academy_name', 'Cricket Academy');
    $address = \App\Models\Setting::get('academy_address', '');
    $phone = \App\Models\Setting::get('academy_phone', '');
    $email = \App\Models\Setting::get('academy_email', '');
    $logo = \App\Models\Setting::get('academy_logo');
    $logoUrl = $logo ? \App\Helpers\StorageHelper::url($logo) : null;
    $photoUrl = $student->photo ? \App\Helpers\StorageHelper::url($student->photo) : null;
    $batch = $student->activeBatches->first();
    $pretty = fn ($v) => $v ? ucwords(str_replace('_', ' ', $v)) : '—';
@endphp

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Admission Form — {{ $student->full_name }} · {{ $academy }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #eef1f6; color: #1f2540; padding: 24px 12px 48px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .toolbar {
            max-width: 800px; margin: 0 auto 16px; display: flex; gap: 8px;
            justify-content: flex-end; flex-wrap: wrap;
        }
        .toolbar a, .toolbar button {
            display: inline-block; padding: 9px 16px; font-size: 13px; font-weight: 600;
            border-radius: 8px; border: 1px solid #c9d2e3; background: #fff; color: #1f2540;
            cursor: pointer; text-decoration: none;
        }
        .toolbar .primary { background: #1b2358; border-color: #1b2358; color: #fff; }
        .toolbar .whatsapp { background: #00ab55; border-color: #00ab55; color: #fff; }
        .sheet {
            max-width: 800px; margin: 0 auto; background: #fff; border-radius: 14px;
            box-shadow: 0 10px 40px rgba(27, 35, 88, .12); overflow: hidden;
        }
        .masthead {
            background: linear-gradient(120deg, #0d1030, #1b2358 55%, #3b52c9);
            color: #fff; padding: 26px 34px; display: flex; align-items: center; gap: 18px;
        }
        .masthead .logo {
            width: 62px; height: 62px; border-radius: 14px; background: rgba(255,255,255,.12);
            display: grid; place-content: center; flex-shrink: 0; overflow: hidden;
            border: 1px solid rgba(255,255,255,.25);
        }
        .masthead .logo img { width: 62px; height: 62px; object-fit: contain; }
        .masthead h1 { font-size: 22px; letter-spacing: .3px; }
        .masthead p { font-size: 11.5px; opacity: .8; margin-top: 3px; }
        .ribbon {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;
            background: #f4f6fc; border-bottom: 1px solid #e3e8f4; padding: 12px 34px;
        }
        .ribbon .doc-title { font-size: 13px; font-weight: 800; letter-spacing: 3px; color: #1b2358; text-transform: uppercase; }
        .ribbon .meta { font-size: 12px; color: #5a6280; }
        .ribbon .meta b { color: #1f2540; }
        .body { padding: 26px 34px 30px; }
        .head-grid { display: flex; gap: 24px; align-items: flex-start; }
        .head-grid .who { flex: 1; min-width: 0; }
        .student-name { font-size: 24px; font-weight: 800; color: #1b2358; }
        .student-sub { font-size: 13px; color: #5a6280; margin-top: 3px; }
        .badge {
            display: inline-block; margin-top: 10px; padding: 4px 12px; font-size: 11px; font-weight: 700;
            border-radius: 999px; background: #e7f7ef; color: #00754a; letter-spacing: .5px; text-transform: uppercase;
        }
        .photo {
            width: 118px; height: 140px; border-radius: 10px; border: 2px solid #e3e8f4; flex-shrink: 0;
            background: #f4f6fc; display: grid; place-content: center; overflow: hidden;
            font-size: 11px; color: #8a92ab; text-align: center;
        }
        .photo img { width: 118px; height: 140px; object-fit: cover; }
        h2.section {
            font-size: 12px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #3b52c9;
            border-bottom: 2px solid #e3e8f4; padding-bottom: 6px; margin: 26px 0 14px;
        }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px 20px; }
        .grid.two { grid-template-columns: repeat(2, 1fr); }
        .field .k { font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #8a92ab; }
        .field .v { font-size: 14px; font-weight: 600; margin-top: 2px; word-break: break-word; }
        .note {
            margin-top: 26px; padding: 14px 16px; font-size: 12px; line-height: 1.6; color: #5a6280;
            background: #f8f9fd; border: 1px solid #e3e8f4; border-radius: 10px;
        }
        .sign-row { display: flex; justify-content: space-between; gap: 30px; margin-top: 44px; }
        .sign { flex: 1; text-align: center; }
        .sign .line { border-top: 1.5px solid #9aa3bd; margin-bottom: 6px; }
        .sign p { font-size: 11.5px; font-weight: 600; color: #5a6280; }
        .foot {
            border-top: 1px solid #e3e8f4; margin-top: 30px; padding-top: 12px;
            display: flex; justify-content: space-between; font-size: 10.5px; color: #8a92ab;
        }
        @media (max-width: 640px) {
            .grid, .grid.two { grid-template-columns: repeat(2, 1fr); }
            .masthead, .ribbon, .body { padding-left: 18px; padding-right: 18px; }
            .head-grid { flex-direction: column-reverse; align-items: center; text-align: center; }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; border-radius: 0; max-width: none; }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        @if (! $public)
            <a href="{{ route('admin.students.show', $student) }}">← Back to Profile</a>
            @if ($waLink)
                <a class="whatsapp" href="{{ $waLink }}" target="_blank" rel="noopener">💬 Send to Guardian</a>
            @endif
        @endif
        <button class="primary" onclick="window.print()">🖨 Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="masthead">
            <span class="logo">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" />
                @else
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="#fff" stroke-width="1.6" />
                        <path d="M12 3C12 3 9 7 9 12C9 17 12 21 12 21M12 3C12 3 15 7 15 12C15 17 12 21 12 21" stroke="#fff" stroke-width="1.6" />
                    </svg>
                @endif
            </span>
            <div>
                <h1>{{ $academy }}</h1>
                <p>
                    {{ $address ?: 'Cricket Coaching & Training Academy' }}
                    @if ($phone) · {{ $phone }} @endif
                    @if ($email) · {{ $email }} @endif
                </p>
            </div>
        </div>

        <div class="ribbon">
            <span class="doc-title">Admission Form</span>
            <span class="meta">
                Admission No: <b>{{ $student->student_code }}</b>
                &nbsp;·&nbsp; Date: <b>{{ $student->admission_date?->format('d M Y') }}</b>
            </span>
        </div>

        <div class="body">
            <div class="head-grid">
                <div class="who">
                    <div class="student-name">{{ $student->full_name }}</div>
                    <div class="student-sub">
                        {{ $student->age }} years · {{ ucfirst($student->gender) }}
                        @if ($batch) · {{ $batch->name }} @endif
                    </div>
                    <span class="badge">Admission Confirmed</span>
                </div>
                <div class="photo">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $student->full_name }}" />
                    @else
                        <span>Student<br />Photograph</span>
                    @endif
                </div>
            </div>

            <h2 class="section">Personal Details</h2>
            <div class="grid">
                <div class="field"><div class="k">Date of Birth</div><div class="v">{{ $student->date_of_birth?->format('d M Y') ?? '—' }}</div></div>
                <div class="field"><div class="k">Gender</div><div class="v">{{ ucfirst($student->gender) }}</div></div>
                <div class="field"><div class="k">Blood Group</div><div class="v">{{ $student->blood_group ?: '—' }}</div></div>
                <div class="field"><div class="k">School</div><div class="v">{{ $student->school_name ?: '—' }}</div></div>
                <div class="field"><div class="k">Student Phone</div><div class="v">{{ $student->phone ?: '—' }}</div></div>
                <div class="field"><div class="k">Student Email</div><div class="v">{{ $student->email ?: '—' }}</div></div>
            </div>

            <h2 class="section">Guardian Details</h2>
            <div class="grid">
                <div class="field"><div class="k">Guardian Name</div><div class="v">{{ $student->guardian_name }}</div></div>
                <div class="field"><div class="k">Relation</div><div class="v">{{ $pretty($student->guardian_relation) }}</div></div>
                <div class="field"><div class="k">Mobile</div><div class="v">{{ $student->guardian_phone }}</div></div>
                <div class="field"><div class="k">Email</div><div class="v">{{ $student->guardian_email ?: '—' }}</div></div>
            </div>

            <h2 class="section">Address</h2>
            <div class="grid two">
                <div class="field"><div class="k">Residential Address</div>
                    <div class="v">{{ $student->address ?: '—' }}</div></div>
                <div class="field"><div class="k">City / State / Pincode</div>
                    <div class="v">{{ collect([$student->city, $student->state, $student->pincode])->filter()->implode(', ') ?: '—' }}</div></div>
            </div>

            <h2 class="section">Cricket Profile</h2>
            <div class="grid">
                <div class="field"><div class="k">Playing Role</div><div class="v">{{ $student->playing_role_label }}</div></div>
                <div class="field"><div class="k">Batting Style</div><div class="v">{{ $pretty($student->batting_style) }}</div></div>
                <div class="field"><div class="k">Bowling Style</div><div class="v">{{ $pretty($student->bowling_style) }}</div></div>
                <div class="field"><div class="k">Batch</div><div class="v">{{ $batch->name ?? 'To be assigned' }}</div></div>
                <div class="field"><div class="k">Coach</div><div class="v">{{ $batch?->coach?->full_name ?? '—' }}</div></div>
                <div class="field"><div class="k">Joining Date</div><div class="v">{{ $student->admission_date?->format('d M Y') }}</div></div>
            </div>

            @if ($student->medical_notes)
                <h2 class="section">Medical Notes</h2>
                <div class="field"><div class="v" style="font-weight: 500;">{{ $student->medical_notes }}</div></div>
            @endif

            <div class="note">
                <b>Declaration:</b> I hereby confirm that the information provided above is true and complete.
                I authorise {{ $academy }} to enrol the student named above and agree to abide by the academy's
                rules regarding training, attendance, fees and conduct. The academy will take reasonable care of the
                student during training hours.
            </div>

            <div class="sign-row">
                <div class="sign"><div class="line"></div><p>Guardian's Signature</p></div>
                <div class="sign"><div class="line"></div><p>Authorised Signatory<br />{{ $academy }}</p></div>
            </div>

            <div class="foot">
                <span>{{ $academy }} — Official Admission Record</span>
                <span>Generated on {{ now()->format('d M Y') }}</span>
            </div>
        </div>
    </div>

</body>

</html>

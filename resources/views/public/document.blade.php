<!DOCTYPE html>
<html lang="en">
@php
    $academy = \App\Models\Setting::get('academy_name', 'Cricket Academy');
    $address = \App\Models\Setting::get('academy_address', '');
    $phone = \App\Models\Setting::get('academy_phone', '');
    $email = \App\Models\Setting::get('academy_email', '');
    $logo = \App\Models\Setting::get('academy_logo');
    $logoUrl = $logo ? \App\Helpers\StorageHelper::url($logo) : null;
    $tone = $badgeTone ?? 'paid';
@endphp

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ $docTitle }} {{ $docNo }} · {{ $academy }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #eef1f6; color: #1f2540; padding: 24px 12px 48px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .toolbar { max-width: 620px; margin: 0 auto 16px; display: flex; justify-content: flex-end; }
        .toolbar button {
            padding: 9px 16px; font-size: 13px; font-weight: 600; border-radius: 8px;
            border: none; background: #1b2358; color: #fff; cursor: pointer;
        }
        .sheet {
            max-width: 620px; margin: 0 auto; background: #fff; border-radius: 14px;
            box-shadow: 0 10px 40px rgba(27, 35, 88, .12); overflow: hidden;
        }
        .masthead {
            background: linear-gradient(120deg, #0d1030, #1b2358 55%, #3b52c9);
            color: #fff; padding: 22px 28px; display: flex; align-items: center; gap: 15px;
        }
        .masthead .logo {
            width: 52px; height: 52px; border-radius: 12px; background: rgba(255,255,255,.12);
            display: grid; place-content: center; flex-shrink: 0; overflow: hidden;
            border: 1px solid rgba(255,255,255,.25);
        }
        .masthead .logo img { width: 52px; height: 52px; object-fit: contain; }
        .masthead h1 { font-size: 19px; letter-spacing: .3px; }
        .masthead p { font-size: 11px; opacity: .8; margin-top: 3px; }
        .ribbon {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;
            background: #f4f6fc; border-bottom: 1px solid #e3e8f4; padding: 11px 28px;
        }
        .ribbon .doc-title { font-size: 12px; font-weight: 800; letter-spacing: 2.5px; color: #1b2358; text-transform: uppercase; }
        .ribbon .meta { font-size: 12px; color: #5a6280; }
        .ribbon .meta b { color: #1f2540; }
        .body { padding: 22px 28px 26px; }
        .who { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
        .student-name { font-size: 20px; font-weight: 800; color: #1b2358; }
        .student-sub { font-size: 12.5px; color: #5a6280; margin-top: 2px; }
        .badge {
            padding: 5px 14px; font-size: 11px; font-weight: 800; border-radius: 999px;
            letter-spacing: .5px; text-transform: uppercase; white-space: nowrap;
        }
        .badge.paid { background: #e7f7ef; color: #00754a; }
        .badge.due { background: #fdeeee; color: #c0392b; }
        table.rows { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.rows td { padding: 9px 0; border-bottom: 1px solid #eef1f8; font-size: 13.5px; }
        table.rows td.k { color: #8a92ab; font-weight: 600; }
        table.rows td.v { text-align: right; font-weight: 700; }
        .amount-box { text-align: center; padding: 20px 0 6px; }
        .amount-box .label { font-size: 10.5px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #8a92ab; }
        .amount-box .value { font-size: 32px; font-weight: 800; color: {{ $tone === 'due' ? '#c0392b' : '#00754a' }}; margin-top: 4px; }
        .footnote {
            margin-top: 16px; padding: 12px 16px; font-size: 12.5px; line-height: 1.5; color: #5a6280;
            background: #f8f9fd; border: 1px solid #e3e8f4; border-radius: 10px; text-align: center;
        }
        .foot {
            border-top: 1px solid #e3e8f4; margin-top: 22px; padding-top: 11px;
            display: flex; justify-content: space-between; font-size: 10.5px; color: #8a92ab;
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
        <button onclick="window.print()">🖨 Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="masthead">
            <span class="logo">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" />
                @else
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
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
            <span class="doc-title">{{ $docTitle }}</span>
            <span class="meta">No: <b>{{ $docNo }}</b> &nbsp;·&nbsp; Date: <b>{{ $docDate }}</b></span>
        </div>

        <div class="body">
            <div class="who">
                <div>
                    <div class="student-name">{{ $student?->full_name }}</div>
                    <div class="student-sub">{{ $student?->student_code }}</div>
                </div>
                <span class="badge {{ $tone }}">{{ $badge }}</span>
            </div>

            <table class="rows">
                @foreach ($rows as $label => $value)
                    <tr>
                        <td class="k">{{ $label }}</td>
                        <td class="v">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>

            <div class="amount-box">
                <div class="label">{{ $amountLabel }}</div>
                <div class="value">{{ $amount }}</div>
            </div>

            @if ($footNote)
                <div class="footnote">{{ $footNote }}</div>
            @endif

            <div class="foot">
                <span>{{ $academy }} — this is a system-generated document</span>
                <span>Generated on {{ now()->format('d M Y') }}</span>
            </div>
        </div>
    </div>

</body>

</html>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <style>
        /* DomPDF has no access to the app stylesheet, so styles are inline. */
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2937; margin: 0; }
        .header { border-bottom: 2px solid #4361ee; padding-bottom: 8px; margin-bottom: 12px; }
        .academy { font-size: 16px; font-weight: bold; color: #4361ee; margin: 0; }
        .address { font-size: 8px; color: #6b7280; margin: 2px 0 0; }
        h1 { font-size: 13px; margin: 10px 0 2px; }
        .meta { font-size: 8px; color: #6b7280; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4361ee; color: #fff; text-align: left; padding: 5px 4px; font-size: 9px; }
        td { padding: 4px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 7px; color: #9ca3af; text-align: center; }
        .empty { text-align: center; padding: 20px; color: #9ca3af; }
    </style>
</head>

<body>
    <div class="header">
        <p class="academy">{{ $academy }}</p>
        @if ($address)
            <p class="address">{{ $address }}</p>
        @endif
    </div>

    <h1>{{ $title }}</h1>
    <p class="meta">{{ $meta }} · Generated {{ now()->format('d M Y, h:i A') }}</p>

    @if (count($rows) === 0)
        <p class="empty">No records match this report.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $academy }} · {{ $title }} · Page <script type="text/php">
            if (isset($pdf)) { $pdf->page_text(280, 820, "{PAGE_NUM} of {PAGE_COUNT}", null, 7, [0.6,0.6,0.6]); }
        </script>
    </div>
</body>

</html>

@php
    /**
     * Civil Service Form No. 48 — Daily Time Record.
     * Rendered twice on one sheet: one copy for the employee, one for file.
     */
    // Dates arrive as CarbonImmutable (Date::use), so accept any date-time.
    $fmt = fn (?\DateTimeInterface $t): string => $t?->format('g:i') ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>DTR — {{ $month->format('F Y') }} ({{ $sheets->count() }})</title>
    <style>
        @page {
            size: 8.5in 11in;
            margin: 0.35in 0.3in;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif;
            font-size: 8pt;
            color: #000;
            background: #fff;
        }

        /* Two copies side by side, each half the sheet. */
        .sheet {
            display: flex;
            gap: 0.3in;
            align-items: flex-start;
        }

        /* One employee per sheet when printing a batch. */
        .sheet + .sheet { margin-top: 0.4in; }

        @media print {
            .sheet { break-after: page; page-break-after: always; }
            .sheet:last-child { break-after: auto; page-break-after: auto; }
            .sheet + .sheet { margin-top: 0; }
        }

        .copy {
            flex: 1 1 0;
            min-width: 0;
        }

        .head { text-align: center; line-height: 1.25; }
        .form-no { font-size: 7pt; font-style: italic; text-align: left; }
        .title { font-size: 11pt; font-weight: bold; letter-spacing: .5px; }
        .rule { margin: 1px 0 4px; }

        .name {
            margin-top: 6px;
            border-bottom: 1px solid #000;
            text-align: center;
            font-weight: bold;
            min-height: 14px;
        }
        .name-caption { text-align: center; font-size: 7pt; font-style: italic; }

        .meta { margin-top: 5px; font-size: 7.5pt; }
        .meta .line { border-bottom: 1px solid #000; display: inline-block; min-width: 90px; }

        table.dtr {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        table.dtr th, table.dtr td {
            border: 1px solid #000;
            text-align: center;
            padding: 0;
            height: 13px;
            font-size: 7.5pt;
        }

        table.dtr th { font-weight: normal; font-size: 6.5pt; line-height: 1.05; }
        .day-col { width: 20px; }
        .ut-col { width: 26px; }
        .weekend td { background: #eee; }

        .cert {
            margin-top: 6px;
            font-size: 7pt;
            text-align: justify;
            line-height: 1.3;
        }

        .sig {
            margin-top: 16px;
            border-top: 1px solid #000;
            width: 70%;
            margin-left: auto;
            text-align: center;
            font-size: 7pt;
        }

        .verify { margin-top: 10px; font-size: 7pt; }

        .no-print { margin: 10px 0; text-align: center; }
        .no-print button {
            font: inherit; padding: 6px 14px; cursor: pointer;
            border: 1px solid #333; background: #f3f3f3; border-radius: 4px;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()">
        Print {{ $sheets->count() }} DTR{{ $sheets->count() === 1 ? '' : 's' }}
    </button>
</div>

@foreach ($sheets as $sheet)
    @php
        $employee = $sheet['employee'];
        $rows = $sheet['rows'];
        $totalUndertime = (int) $rows->sum('undertime_minutes');
    @endphp
<div class="sheet">
    {{-- Two identical copies: employee's and office file copy. --}}
    @foreach (['Employee copy', 'Office copy'] as $copy)
        <div class="copy">
            <div class="form-no">Civil Service Form No. 48</div>
            <div class="head">
                <div class="title">DAILY TIME RECORD</div>
                <div class="rule">- - - - - o 0 o - - - - -</div>
            </div>

            <div class="name">{{ $employee->full_name }}</div>
            <div class="name-caption">(Name)</div>

            <div class="meta">
                For the month of <span class="line">{{ $month->format('F Y') }}</span><br>
                Official hours for arrival <span class="line">{{ $shiftStart }}</span>
                and departure <span class="line">{{ $shiftEnd }}</span>
            </div>

            <table class="dtr">
                <thead>
                    <tr>
                        <th rowspan="2" class="day-col">Day</th>
                        <th colspan="2">A.M.</th>
                        <th colspan="2">P.M.</th>
                        <th colspan="2">UNDERTIME</th>
                    </tr>
                    <tr>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th class="ut-col">Hours</th>
                        <th class="ut-col">Min.</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $date = $month->copy()->day($day);
                            $record = $rows[$day] ?? null;
                            $undertime = (int) ($record->undertime_minutes ?? 0);
                        @endphp
                        <tr class="{{ $date->isWeekend() ? 'weekend' : '' }}">
                            <td>{{ $day }}</td>
                            <td>{{ $fmt($record?->time_in) }}</td>
                            <td>{{ $fmt($record?->break_out) }}</td>
                            <td>{{ $fmt($record?->break_in) }}</td>
                            <td>{{ $fmt($record?->time_out) }}</td>
                            <td>{{ $undertime > 0 ? intdiv($undertime, 60) : '' }}</td>
                            <td>{{ $undertime > 0 ? $undertime % 60 : '' }}</td>
                        </tr>
                    @endfor
                    <tr>
                        <td colspan="5" style="text-align: right; padding-right: 3px;"><strong>TOTAL</strong></td>
                        <td><strong>{{ $totalUndertime > 0 ? intdiv($totalUndertime, 60) : '' }}</strong></td>
                        <td><strong>{{ $totalUndertime > 0 ? $totalUndertime % 60 : '' }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="cert">
                I CERTIFY on my honor that the above is a true and correct report of the
                hours of work performed, record of which was made daily at the time of
                arrival at and departure from office.
            </div>

            <div class="sig">(Signature)</div>

            <div class="verify">
                Verified as to the prescribed office hours:
            </div>

            <div class="sig">In Charge</div>

            <div style="margin-top:4px; font-size:6pt; text-align:right; font-style:italic;">{{ $copy }}</div>
        </div>
    @endforeach
</div>
@endforeach
</body>
</html>

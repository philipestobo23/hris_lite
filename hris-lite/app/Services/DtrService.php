<?php

namespace App\Services;

use App\Enums\DtrStatus;
use App\Models\AttendanceLog;
use App\Models\DailyTimeRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Scopes\BranchScope;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Turns an employee's raw biometric punches for one day into a resolved
 * {@see DailyTimeRecord}: first IN / last OUT, hours worked, lateness,
 * undertime, overtime and night differential, plus the day's status.
 *
 * Approved leaves and holidays override the status automatically.
 */
class DtrService
{
    /**
     * A day longer than this has the unpaid break deducted from it. Shorter
     * days (half-days, early-outs) are assumed to have been worked straight.
     */
    private const BREAK_THRESHOLD_MINUTES = 300; // 5 hours

    /**
     * How long after the IN punch we will still accept an OUT punch. Generous
     * enough for a long shift plus overtime, so a day that runs past midnight
     * still pairs up.
     */
    private const MAX_SHIFT_HOURS = 16;

    public function __construct(private SettingsManager $settings) {}

    /**
     * Build (or rebuild) the daily time record for one employee on one date.
     */
    public function buildFor(Employee $employee, DateTimeInterface|string $date): DailyTimeRecord
    {
        $day = CarbonImmutable::parse($date)->startOfDay();

        $shiftStart = $this->timeOn($day, $this->setting('attendance.shift_start', '08:00'));
        $shiftEnd = $this->timeOn($day, $this->setting('attendance.shift_end', '17:00'));

        // A shift ending at or before it starts runs overnight into the next day.
        if ($shiftEnd <= $shiftStart) {
            $shiftEnd = $shiftEnd->addDay();
        }

        [$timeIn, $breakOut, $breakIn, $timeOut] = array_map(
            fn (?DateTimeInterface $punch): ?CarbonImmutable => $punch === null
                ? null
                : CarbonImmutable::instance($punch),
            $this->pairPunches($employee, $day),
        );

        $metrics = $this->computeMetrics($timeIn, $timeOut, $breakOut, $breakIn, $shiftStart, $shiftEnd);

        $holiday = Holiday::query()
            ->withoutGlobalScope(BranchScope::class)
            ->forBranch($employee->branch_id)
            ->whereDate('date', $day)
            ->first();

        $leave = Leave::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('employee_id', $employee->id)
            ->approved()
            ->covering($day->toDateString())
            ->first();

        return DailyTimeRecord::query()
            ->withoutGlobalScope(BranchScope::class)
            ->updateOrCreate(
                ['employee_id' => $employee->id, 'work_date' => $day->toDateString()],
                [
                    'branch_id' => $employee->branch_id,
                    'holiday_id' => $holiday?->id,
                    'time_in' => $timeIn,
                    'break_out' => $breakOut,
                    'break_in' => $breakIn,
                    'time_out' => $timeOut,
                    'hours_worked' => $metrics['hours_worked'],
                    'late_minutes' => $metrics['late_minutes'],
                    'undertime_minutes' => $metrics['undertime_minutes'],
                    'overtime_minutes' => $metrics['overtime_minutes'],
                    'night_differential_minutes' => $metrics['night_differential_minutes'],
                    'status' => $this->resolveStatus($timeIn, $holiday !== null, $leave !== null),
                    'remarks' => $this->resolveRemarks($timeIn, $timeOut, $holiday?->name, $leave?->type->label()),
                ],
            );
    }

    /**
     * Build every day in an inclusive date range for one employee.
     *
     * @return \Illuminate\Support\Collection<int, DailyTimeRecord>
     */
    public function buildRange(Employee $employee, DateTimeInterface|string $from, DateTimeInterface|string $to)
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();

        $records = collect();

        for ($day = $start; $day <= $end; $day = $day->addDay()) {
            $records->push($this->buildFor($employee, $day));
        }

        return $records;
    }

    /**
     * First IN and last OUT for the day. Falls back to the earliest/latest
     * punch when the terminal does not report an in/out direction.
     *
     * Any punches in between are treated as the meal break (first = break out,
     * last = break in), which fills the CSC form's four columns.
     *
     * @return array{0: Carbon|null, 1: Carbon|null, 2: Carbon|null, 3: Carbon|null}
     */
    private function pairPunches(Employee $employee, CarbonImmutable $day): array
    {
        $sameDay = $this->punchesBetween($employee, $day->startOfDay(), $day->endOfDay());

        if ($sameDay->isEmpty()) {
            return [null, null, null, null];
        }

        // Prefer an explicit IN punch; otherwise the earliest punch of the day.
        $timeIn = ($sameDay->firstWhere('status', 'in') ?? $sameDay->first())->punched_at;

        // The OUT punch can land after midnight, so look past the calendar day
        // — but never as far as the next day's shift, otherwise we would
        // swallow tomorrow's first punch as today's time out.
        $cap = CarbonImmutable::instance($timeIn)->addHours(self::MAX_SHIFT_HOURS);
        $nextShiftStart = $this->timeOn($day->addDay(), $this->setting('attendance.shift_start', '08:00'));

        if ($nextShiftStart < $cap) {
            $cap = $nextShiftStart;
        }

        $after = $this->punchesBetween($employee, $timeIn, $cap)
            ->filter(fn (AttendanceLog $punch): bool => $punch->punched_at > $timeIn);

        $timeOut = $after
            ->filter(fn (AttendanceLog $punch): bool => $punch->status !== 'in')
            ->last()?->punched_at;

        // A lone IN punch means an incomplete day — we know they showed up but
        // cannot measure the span.
        if ($timeOut === null) {
            return [$timeIn, null, null, null];
        }

        // Punches strictly between the first IN and last OUT are the meal break.
        $middle = $after->filter(
            fn (AttendanceLog $punch): bool => $punch->punched_at < $timeOut,
        )->values();

        // A single stray punch is ambiguous, so only pair a real break.
        $breakOut = $middle->count() >= 2 ? $middle->first()->punched_at : null;
        $breakIn = $middle->count() >= 2 ? $middle->last()->punched_at : null;

        return [$timeIn, $breakOut, $breakIn, $timeOut];
    }

    /**
     * @return Collection<int, AttendanceLog>
     */
    private function punchesBetween(Employee $employee, DateTimeInterface $from, DateTimeInterface $to)
    {
        return AttendanceLog::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('employee_id', $employee->id)
            ->whereBetween('punched_at', [$from, $to])
            ->orderBy('punched_at')
            ->get();
    }

    /**
     * @return array{hours_worked: float, late_minutes: int, undertime_minutes: int, overtime_minutes: int, night_differential_minutes: int}
     */
    private function computeMetrics(
        ?CarbonImmutable $timeIn,
        ?CarbonImmutable $timeOut,
        ?CarbonImmutable $breakOut,
        ?CarbonImmutable $breakIn,
        CarbonImmutable $shiftStart,
        CarbonImmutable $shiftEnd,
    ): array {
        $empty = [
            'hours_worked' => 0.0,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
            'night_differential_minutes' => 0,
        ];

        if ($timeIn === null) {
            return $empty;
        }

        // Lateness is knowable from the IN punch alone.
        $graceEnd = $shiftStart->addMinutes((int) $this->setting('payroll.grace_period', 15));
        $late = max(0, $this->minutesBetween($graceEnd, $timeIn));

        if ($timeOut === null) {
            return [...$empty, 'late_minutes' => $late];
        }

        $span = $this->minutesBetween($timeIn, $timeOut);

        // A punched-out break is measured; otherwise fall back to the standard
        // deduction for days long enough to include one.
        $break = $breakOut !== null && $breakIn !== null
            ? max(0, $this->minutesBetween($breakOut, $breakIn))
            : ($span > self::BREAK_THRESHOLD_MINUTES ? (int) $this->setting('attendance.break_minutes', 60) : 0);

        $worked = max(0, $span - $break);

        return [
            'hours_worked' => round($worked / 60, 2),
            'late_minutes' => $late,
            'undertime_minutes' => max(0, $this->minutesBetween($timeOut, $shiftEnd)),
            'overtime_minutes' => max(0, $this->minutesBetween($shiftEnd, $timeOut)),
            'night_differential_minutes' => $this->nightDifferentialMinutes($timeIn, $timeOut),
        ];
    }

    /**
     * Minutes of the worked span that fall inside the night-differential
     * window. The window normally crosses midnight, so it is tested against
     * the previous, current and next day.
     */
    private function nightDifferentialMinutes(CarbonImmutable $timeIn, CarbonImmutable $timeOut): int
    {
        $from = $this->setting('attendance.night_diff_start', '22:00');
        $to = $this->setting('attendance.night_diff_end', '06:00');

        $total = 0;

        foreach ([-1, 0, 1] as $offset) {
            $base = $timeIn->startOfDay()->addDays($offset);
            $windowStart = $this->timeOn($base, $from);
            $windowEnd = $this->timeOn($base, $to);

            if ($windowEnd <= $windowStart) {
                $windowEnd = $windowEnd->addDay();
            }

            $total += $this->overlapMinutes($timeIn, $timeOut, $windowStart, $windowEnd);
        }

        return $total;
    }

    /** Holidays win over leaves; both win over what the punches say. */
    private function resolveStatus(?CarbonImmutable $timeIn, bool $isHoliday, bool $onLeave): DtrStatus
    {
        return match (true) {
            $isHoliday => DtrStatus::Holiday,
            $onLeave => DtrStatus::OnLeave,
            $timeIn !== null => DtrStatus::Present,
            default => DtrStatus::Absent,
        };
    }

    private function resolveRemarks(
        ?CarbonImmutable $timeIn,
        ?CarbonImmutable $timeOut,
        ?string $holidayName,
        ?string $leaveLabel,
    ): ?string {
        $notes = array_filter([
            $holidayName,
            $leaveLabel,
            $timeIn !== null && $timeOut === null ? 'No time out recorded.' : null,
        ]);

        return $notes === [] ? null : implode(' — ', $notes);
    }

    /** Anchor a "HH:MM" setting onto a specific day. */
    private function timeOn(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return $day->startOfDay()->setTime($hour, $minute);
    }

    /**
     * Signed whole minutes from $from to $to. Uses timestamps so the result
     * does not depend on Carbon's diff-sign conventions.
     */
    private function minutesBetween(DateTimeInterface $from, DateTimeInterface $to): int
    {
        return (int) round(($to->getTimestamp() - $from->getTimestamp()) / 60);
    }

    private function overlapMinutes(
        DateTimeInterface $aStart,
        DateTimeInterface $aEnd,
        DateTimeInterface $bStart,
        DateTimeInterface $bEnd,
    ): int {
        $start = max($aStart->getTimestamp(), $bStart->getTimestamp());
        $end = min($aEnd->getTimestamp(), $bEnd->getTimestamp());

        return $end > $start ? (int) round(($end - $start) / 60) : 0;
    }

    private function setting(string $key, string|int $default): string
    {
        return (string) $this->settings->get($key, $default);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DailyTimeRecord;
use App\Models\Employee;
use App\Services\DtrService;
use App\Services\SettingsManager;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DtrController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DailyTimeRecord::class);

        [$from, $to] = $this->range($request);

        $employees = Employee::query()
            ->with('department:id,name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_no', 'biometric_id', 'department_id']);

        // How many days each employee already has built inside the range, so
        // the list can show progress at a glance.
        $generated = DailyTimeRecord::query()
            ->whereBetween('work_date', [$from, $to])
            ->selectRaw('employee_id, COUNT(*) as days')
            ->groupBy('employee_id')
            ->pluck('days', 'employee_id');

        return Inertia::render('dtr/Index', [
            'employees' => $employees,
            'generated' => $generated,
            'filters' => [
                'year' => (int) $from->format('Y'),
                'month' => (int) $from->format('n'),
                'period' => $request->string('period')->value() ?: 'whole',
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'rangeDays' => $from->diffInDays($to) + 1,
        ]);
    }

    public function build(Request $request, DtrService $dtr): RedirectResponse
    {
        $this->authorize('create', DailyTimeRecord::class);

        $validated = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = CarbonImmutable::parse($validated['from'])->startOfDay();
        // Never build past today — future days have no punches to resolve.
        $to = CarbonImmutable::parse($validated['to'])->startOfDay()
            ->min(CarbonImmutable::now()->startOfDay());

        if ($to < $from) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __('That range is entirely in the future — nothing to generate yet.'),
            ]);

            return back();
        }

        $employees = Employee::query()->whereKey($validated['employee_ids'])->get();
        $days = 0;

        foreach ($employees as $employee) {
            $days += $dtr->buildRange($employee, $from, $to)->count();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':days day(s) generated for :people employee(s).', [
                'days' => $days,
                'people' => $employees->count(),
            ]),
        ]);

        return back();
    }

    /**
     * CSC Form 48 (Daily Time Record). Each employee gets their own sheet with
     * two copies on it; sheets page-break so a batch prints in one go.
     */
    public function print(Request $request, SettingsManager $settings): View
    {
        $this->authorize('viewAny', DailyTimeRecord::class);

        $validated = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $month = CarbonImmutable::createFromFormat('Y-m', $validated['month'])->startOfMonth();

        $employees = Employee::query()
            ->whereKey($validated['employee_ids'])
            ->orderBy('first_name')
            ->get();

        $records = DailyTimeRecord::query()
            ->whereIn('employee_id', $employees->modelKeys())
            ->whereBetween('work_date', [$month->startOfMonth(), $month->endOfMonth()])
            ->get()
            ->groupBy('employee_id');

        // One sheet per employee: the employee plus their days keyed by day-of-month.
        $sheets = $employees->map(fn (Employee $employee): array => [
            'employee' => $employee,
            'rows' => ($records[$employee->id] ?? collect())
                ->keyBy(fn (DailyTimeRecord $record): int => (int) $record->work_date->format('j')),
        ]);

        return view('dtr.print', [
            'sheets' => $sheets,
            'month' => $month,
            'daysInMonth' => (int) $month->format('t'),
            'shiftStart' => $settings->get('attendance.shift_start', '08:00'),
            'shiftEnd' => $settings->get('attendance.shift_end', '17:00'),
        ]);
    }

    /**
     * Resolve the requested period into an inclusive date range. The client
     * sends explicit from/to; this re-derives them defensively and falls back
     * to the current month.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(Request $request): array
    {
        $from = $this->parseDate($request->string('from')->value());
        $to = $this->parseDate($request->string('to')->value());

        if ($from === null || $to === null || $to < $from) {
            $month = CarbonImmutable::now()->startOfMonth();

            return [$month, $month->endOfMonth()->startOfDay()];
        }

        return [$from, $to];
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
    }
}

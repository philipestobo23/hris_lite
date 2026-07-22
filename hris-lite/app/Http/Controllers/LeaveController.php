<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Http\Requests\LeaveRequest;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    private const SORTABLE = ['start_date', 'end_date', 'days', 'status', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Leave::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $type = $request->string('type')->trim()->value();
        $sort = in_array($request->query('sort'), self::SORTABLE, true)
            ? $request->query('sort')
            : 'start_date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $leaves = Leave::query()
            ->with(['employee:id,first_name,last_name,employee_no', 'approver:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('employee', function ($employee) use ($search) {
                    $employee->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_no', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('leaves/Index', [
            'leaves' => $leaves,
            'types' => LeaveType::options(),
            'statuses' => LeaveStatus::options(),
            'can' => [
                'approve' => $request->user()->can('leave.approve'),
                'create' => $request->user()->can('leave.create'),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Leave::class);

        return Inertia::render('leaves/Create', $this->formOptions());
    }

    public function store(LeaveRequest $request): RedirectResponse
    {
        $this->authorize('create', Leave::class);

        Leave::create([
            ...$request->payload(),
            'status' => LeaveStatus::Pending,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leave filed.')]);

        return to_route('leaves.index');
    }

    public function edit(Leave $leave): Response
    {
        $this->authorize('update', $leave);

        return Inertia::render('leaves/Edit', [
            'leave' => $leave,
            ...$this->formOptions(),
        ]);
    }

    public function update(LeaveRequest $request, Leave $leave): RedirectResponse
    {
        $this->authorize('update', $leave);

        if ($blocked = $this->rejectIfDecided($leave, 'edited')) {
            return $blocked;
        }

        $leave->update($request->payload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leave updated.')]);

        return to_route('leaves.index');
    }

    public function approve(Request $request, Leave $leave): RedirectResponse
    {
        $this->authorize('approve', $leave);

        if ($blocked = $this->rejectIfDecided($leave, 'approved')) {
            return $blocked;
        }

        $leave->update([
            'status' => LeaveStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leave approved.')]);

        return back();
    }

    public function reject(Request $request, Leave $leave): RedirectResponse
    {
        $this->authorize('approve', $leave);

        if ($blocked = $this->rejectIfDecided($leave, 'rejected')) {
            return $blocked;
        }

        $leave->update([
            'status' => LeaveStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leave rejected.')]);

        return back();
    }

    public function destroy(Leave $leave): RedirectResponse
    {
        $this->authorize('delete', $leave);

        $leave->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leave deleted.')]);

        return back();
    }

    /**
     * A filing that has already been decided is final. Enforced here rather
     * than in the policy so the Super Admin Gate::before bypass cannot skip
     * the state machine.
     */
    private function rejectIfDecided(Leave $leave, string $action): ?RedirectResponse
    {
        if ($leave->status === LeaveStatus::Pending) {
            return null;
        }

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => __('Only a pending leave can be :action.', ['action' => $action]),
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'employee_no']),
            'types' => LeaveType::options(),
        ];
    }
}

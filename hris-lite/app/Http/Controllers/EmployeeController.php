<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentStatus;
use App\Enums\SalaryType;
use App\Exports\EmployeesExport;
use App\Http\Requests\EmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Scopes\BranchScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeController extends Controller
{
    private const SORTABLE = ['employee_no', 'first_name', 'last_name', 'employment_status', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $filters = $this->filters($request);
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'last_name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $employees = Employee::query()
            ->with(['branch:id,name', 'department:id,name', 'position:id,name'])
            ->filter($filters)
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'filters' => [...$filters, 'sort' => $sort, 'direction' => $direction],
            ...$this->filterOptions(),
        ]);
    }

    public function show(Employee $employee): Response
    {
        $this->authorize('view', $employee);

        $employee->load(['branch:id,name', 'department:id,name', 'position:id,name', 'documents']);

        return Inertia::render('employees/Show', ['employee' => $employee]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Employee::class);

        return Excel::download(
            new EmployeesExport($this->filters($request)),
            'employees-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function toggleStatus(Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $employee->update([
            'employment_status' => $employee->employment_status->isActive()
                ? EmploymentStatus::Resigned
                : EmploymentStatus::Regular,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $employee->employment_status->isActive()
                ? __('Employee reactivated.')
                : __('Employee deactivated.'),
        ]);

        return back();
    }

    /**
     * @return array{search: string, branch_id: int|null, department_id: int|null, status: string|null}
     */
    private function filters(Request $request): array
    {
        $status = $request->query('status');

        return [
            'search' => $request->string('search')->trim()->value(),
            'branch_id' => $request->integer('branch_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'status' => EmploymentStatus::tryFrom((string) $status)?->value,
        ];
    }

    /**
     * Filter dropdown options for the master list.
     *
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereIn('branch_id', $branches->pluck('id'))->orWhereNull('branch_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        return [
            'branches' => $branches,
            'departments' => $departments,
            'statuses' => EmploymentStatus::options(),
        ];
    }

    public function create(): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('employees/Create', $this->formOptions());
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $employee = DB::transaction(function () use ($request): Employee {
            $data = $request->safe()->except('photo');
            $employee = Employee::create($data);

            if ($request->hasFile('photo')) {
                $employee->update(['photo' => $request->file('photo')->store('employees/photos', 'public')]);
            }

            return $employee;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee created.')]);

        return to_route('employees.edit', $employee);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        $employee->load('documents');

        return Inertia::render('employees/Edit', [
            'employee' => $employee,
            ...$this->formOptions(),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        DB::transaction(function () use ($request, $employee): void {
            $employee->update($request->safe()->except('photo'));

            if ($request->hasFile('photo')) {
                if ($employee->photo) {
                    Storage::disk('public')->delete($employee->photo);
                }
                $employee->update(['photo' => $request->file('photo')->store('employees/photos', 'public')]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee updated.')]);

        return to_route('employees.edit', $employee);
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee deleted.')]);

        return to_route('employees.index');
    }

    /**
     * Options for the form's selects. Uses the accessible branch set (not the
     * active-branch narrowing) so an employee can be assigned to any branch the
     * user manages, and cascades departments/positions client-side.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branchIds = $branches->pluck('id')->all();

        $departments = Department::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        $positions = Position::query()
            ->where('is_active', true)
            ->whereIn('department_id', $departments->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        return [
            'branches' => $branches,
            'departments' => $departments,
            'positions' => $positions,
            'employmentStatuses' => EmploymentStatus::options(),
            'salaryTypes' => SalaryType::options(),
        ];
    }
}

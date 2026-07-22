<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    private const SORTABLE = ['name', 'is_active', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $search = $request->string('search')->trim()->value();
        $sort = in_array($request->query('sort'), self::SORTABLE, true)
            ? $request->query('sort')
            : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $departments = Department::query()
            ->with('branch:id,name')
            ->withCount('positions')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('departments/Index', [
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Department::class);

        return Inertia::render('departments/Create', [
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        Department::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department created.')]);

        return to_route('departments.index');
    }

    public function edit(Department $department): Response
    {
        $this->authorize('update', $department);

        return Inertia::render('departments/Edit', [
            'department' => $department,
            'branches' => $this->branchOptions(),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $department->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department updated.')]);

        return to_route('departments.index');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department deleted.')]);

        return back();
    }

    /**
     * @return Collection<int, Branch>
     */
    private function branchOptions()
    {
        return Branch::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}

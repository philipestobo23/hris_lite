<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionRequest;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    private const SORTABLE = ['name', 'is_active', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Position::class);

        $search = $request->string('search')->trim()->value();
        $sort = in_array($request->query('sort'), self::SORTABLE, true)
            ? $request->query('sort')
            : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $positions = Position::query()
            ->with(['department:id,name,branch_id', 'department.branch:id,name'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('positions/Index', [
            'positions' => $positions,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Position::class);

        return Inertia::render('positions/Create', [
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        $this->authorize('create', Position::class);

        Position::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Position created.')]);

        return to_route('positions.index');
    }

    public function edit(Position $position): Response
    {
        $this->authorize('update', $position);

        return Inertia::render('positions/Edit', [
            'position' => $position,
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $this->authorize('update', $position);

        $position->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Position updated.')]);

        return to_route('positions.index');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $this->authorize('delete', $position);

        $position->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Position deleted.')]);

        return back();
    }

    /**
     * @return Collection<int, Department>
     */
    private function departmentOptions()
    {
        return Department::query()
            ->active()
            ->with('branch:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);
    }
}

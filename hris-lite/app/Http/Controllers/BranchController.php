<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    private const SORTABLE = ['name', 'code', 'is_active', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Branch::class);

        $search = $request->string('search')->trim()->value();
        $sort = in_array($request->query('sort'), self::SORTABLE, true)
            ? $request->query('sort')
            : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $branches = Branch::query()
            ->withCount('departments')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('branches/Index', [
            'branches' => $branches,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Branch::class);

        return Inertia::render('branches/Create');
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);

        Branch::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branch created.')]);

        return to_route('branches.index');
    }

    public function edit(Branch $branch): Response
    {
        $this->authorize('update', $branch);

        return Inertia::render('branches/Edit', [
            'branch' => $branch,
        ]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $branch->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branch updated.')]);

        return to_route('branches.index');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branch deleted.')]);

        return back();
    }
}

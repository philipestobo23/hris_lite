<?php

namespace App\Http\Controllers;

use App\Enums\HolidayType;
use App\Http\Requests\HolidayRequest;
use App\Models\Branch;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    private const SORTABLE = ['date', 'name', 'type', 'pay_rule'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Holiday::class);

        $search = $request->string('search')->trim()->value();
        $year = $request->integer('year') ?: null;
        $type = $request->string('type')->trim()->value();
        $sort = in_array($request->query('sort'), self::SORTABLE, true)
            ? $request->query('sort')
            : 'date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $holidays = Holiday::query()
            ->with('branch:id,name')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($year, fn ($query) => $query->whereYear('date', $year))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('holidays/Index', [
            'holidays' => $holidays,
            'types' => HolidayType::options(),
            'years' => $this->availableYears(),
            'filters' => [
                'search' => $search,
                'year' => $year,
                'type' => $type,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Holiday::class);

        return Inertia::render('holidays/Create', $this->formOptions());
    }

    public function store(HolidayRequest $request): RedirectResponse
    {
        $this->authorize('create', Holiday::class);

        Holiday::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Holiday created.')]);

        return to_route('holidays.index');
    }

    public function edit(Holiday $holiday): Response
    {
        $this->authorize('update', $holiday);

        return Inertia::render('holidays/Edit', [
            'holiday' => $holiday,
            ...$this->formOptions(),
        ]);
    }

    public function update(HolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $this->authorize('update', $holiday);

        $holiday->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Holiday updated.')]);

        return to_route('holidays.index');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('delete', $holiday);

        $holiday->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Holiday deleted.')]);

        return back();
    }

    /**
     * Distinct years that have holidays, newest first, for the index filter.
     *
     * @return list<int>
     */
    private function availableYears(): array
    {
        $years = Holiday::query()
            ->selectRaw('DISTINCT YEAR(date) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->all();

        return array_values(array_map(static fn ($year): int => (int) $year, $years));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'branches' => Branch::query()->active()->orderBy('name')->get(['id', 'name']),
            'types' => HolidayType::options(),
        ];
    }
}

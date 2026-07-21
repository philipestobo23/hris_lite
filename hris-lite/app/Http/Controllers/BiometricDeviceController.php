<?php

namespace App\Http\Controllers;

use App\Enums\DeviceMode;
use App\Enums\DeviceModel;
use App\Http\Requests\BiometricDeviceRequest;
use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Services\ZktecoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BiometricDeviceController extends Controller
{
    private const SORTABLE = ['name', 'model', 'mode', 'is_active', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BiometricDevice::class);

        $search = $request->string('search')->trim()->value();
        $sort = in_array($request->query('sort'), self::SORTABLE, true)
            ? $request->query('sort')
            : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $devices = BiometricDevice::query()
            ->with('branch:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('biometric-devices/Index', [
            'devices' => $devices,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', BiometricDevice::class);

        return Inertia::render('biometric-devices/Create', $this->formOptions());
    }

    public function store(BiometricDeviceRequest $request): RedirectResponse
    {
        $this->authorize('create', BiometricDevice::class);

        BiometricDevice::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Device created.')]);

        return to_route('biometric-devices.index');
    }

    public function edit(BiometricDevice $biometricDevice): Response
    {
        $this->authorize('update', $biometricDevice);

        return Inertia::render('biometric-devices/Edit', [
            'device' => $biometricDevice,
            ...$this->formOptions(),
        ]);
    }

    public function update(BiometricDeviceRequest $request, BiometricDevice $biometricDevice): RedirectResponse
    {
        $this->authorize('update', $biometricDevice);

        $biometricDevice->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Device updated.')]);

        return to_route('biometric-devices.index');
    }

    public function testConnection(BiometricDevice $biometricDevice, ZktecoService $zkteco): RedirectResponse
    {
        // A connection test is a read-only diagnostic, so 'view' is enough
        // (lets view-only Branch Managers ping their branch's devices too).
        $this->authorize('view', $biometricDevice);

        if ($zkteco->testConnectionWithVoice($biometricDevice)) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __(':name is reachable — it should say “Thank you”.', ['name' => $biometricDevice->name]),
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Could not reach :name. Check its IP address, port and network.', ['name' => $biometricDevice->name]),
            ]);
        }

        return back();
    }

    public function destroy(BiometricDevice $biometricDevice): RedirectResponse
    {
        $this->authorize('delete', $biometricDevice);

        $biometricDevice->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Device deleted.')]);

        return back();
    }

    /**
     * Options for the create/edit form's selects.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'branches' => Branch::query()->active()->orderBy('name')->get(['id', 'name']),
            'models' => DeviceModel::options(),
            'modes' => DeviceMode::options(),
        ];
    }
}

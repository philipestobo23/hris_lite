<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\BiometricDevice;
use App\Services\AttendanceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AttendanceLogController extends Controller
{
    private const SORTABLE = ['punched_at', 'status', 'device_user_id'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AttendanceLog::class);

        $search = $request->string('search')->trim()->value();
        $deviceId = $request->integer('device_id') ?: null;
        $date = $request->date('date');
        $sort = in_array($request->query('sort'), self::SORTABLE, true)
            ? $request->query('sort')
            : 'punched_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $logs = AttendanceLog::query()
            ->with(['employee:id,first_name,last_name,employee_no', 'device:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('device_user_id', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($employee) use ($search) {
                            $employee->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('employee_no', 'like', "%{$search}%");
                        });
                });
            })
            ->when($deviceId, fn ($query) => $query->where('biometric_device_id', $deviceId))
            ->when($date, fn ($query) => $query->whereDate('punched_at', $date))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('attendance-logs/Index', [
            'logs' => $logs,
            'devices' => BiometricDevice::query()->orderBy('name')->get(['id', 'name', 'is_active']),
            'filters' => [
                'search' => $search,
                'device_id' => $deviceId,
                'date' => $date?->toDateString(),
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Fetch the enrolled-user roster from a single device and refresh the name
     * and employee link on that device's punches. JSON, one device per call so
     * the modal can show live progress; an unreachable device reports
     * { ok: false } instead of failing the request.
     */
    public function fetchUsersDevice(BiometricDevice $biometricDevice, AttendanceSyncService $syncer): JsonResponse
    {
        $this->authorize('create', AttendanceLog::class);

        $device = ['id' => $biometricDevice->id, 'name' => $biometricDevice->name];

        try {
            $result = $syncer->syncUsers($biometricDevice);

            return response()->json([
                'ok' => true,
                'device' => $device,
                'fetched' => $result['fetched'],
                'linked' => $result['linked'],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'device' => $device,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch attendance punches (times) from a single device and return the
     * result as JSON. The client drives the modal by calling this once per
     * device, so it can show live per-device progress. Never fails the request
     * for an unreachable device — it reports { ok: false } so the loop goes on.
     */
    public function fetchTimeDevice(BiometricDevice $biometricDevice, AttendanceSyncService $syncer): JsonResponse
    {
        $this->authorize('create', AttendanceLog::class);

        $device = ['id' => $biometricDevice->id, 'name' => $biometricDevice->name];

        try {
            $result = $syncer->syncTime($biometricDevice);

            return response()->json([
                'ok' => true,
                'device' => $device,
                'new' => $result['new'],
                'pulled' => $result['pulled'],
                'duplicates' => $result['duplicates'],
                'unmatched' => $result['unmatched'],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'device' => $device,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\Scopes\BranchScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Syncs biometric terminals into the single `attendance_logs` table.
 *
 *  - {@see syncTime()} pulls punches (and labels each with the person's
 *    device-side name + links the matching employee).
 *  - {@see syncUsers()} re-reads the device's enrolled-user roster and refreshes
 *    the name / employee link on the punches already stored for that device.
 */
class AttendanceSyncService
{
    public function __construct(private ZktecoService $zkteco) {}

    /**
     * Pull attendance punches from a device, labelling each with the enrolled
     * name and linking the matching employee.
     *
     * @return array{pulled: int, new: int, duplicates: int, unmatched: int}
     *
     * @throws \RuntimeException when the device cannot be reached.
     */
    public function syncTime(BiometricDevice $device): array
    {
        $logs = $this->zkteco->pullAttendanceLogs($device);

        $employeeMap = $this->employeeMap($device);
        $names = $this->deviceUserNames($device); // best effort

        $new = 0;
        $duplicates = 0;
        $unmatched = 0;

        DB::transaction(function () use ($device, $logs, $names, $employeeMap, &$new, &$duplicates, &$unmatched): void {
            foreach ($logs as $log) {
                $employeeId = $employeeMap[$log['device_user_id']] ?? null;
                $deviceUserName = $names[$log['device_user_id']] ?? null;

                if ($employeeId === null) {
                    $unmatched++;
                }

                $record = AttendanceLog::query()
                    ->withoutGlobalScope(BranchScope::class)
                    ->firstOrNew([
                        'biometric_device_id' => $device->id,
                        'device_user_id' => $log['device_user_id'],
                        'punched_at' => $log['punched_at'],
                    ]);

                if ($record->exists) {
                    $duplicates++;

                    // Back-fill the employee link and/or device-side name if
                    // they became available after the punch was first stored.
                    if ($record->employee_id === null && $employeeId !== null) {
                        $record->employee_id = $employeeId;
                    }

                    if (blank($record->device_user_name) && filled($deviceUserName)) {
                        $record->device_user_name = $deviceUserName;
                    }

                    if ($record->isDirty()) {
                        $record->save();
                    }

                    continue;
                }

                $record->fill([
                    'branch_id' => $device->branch_id,
                    'employee_id' => $employeeId,
                    'device_user_name' => $deviceUserName,
                    'status' => $log['status'],
                    'verify_mode' => $log['verify_mode'],
                ])->save();

                $new++;
            }

            $device->forceFill(['last_synced_at' => Carbon::now()])->save();
        });

        return [
            'pulled' => count($logs),
            'new' => $new,
            'duplicates' => $duplicates,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Re-read the device's enrolled-user roster and refresh the device-side
     * name and employee link on the punches already stored for that device.
     *
     * @return array{fetched: int, linked: int}
     *
     * @throws \RuntimeException when the device cannot be reached.
     */
    public function syncUsers(BiometricDevice $device): array
    {
        $users = $this->zkteco->enrollUsersMap($device);
        $employeeMap = $this->employeeMap($device);

        $fetched = count($users);
        $linked = 0;

        DB::transaction(function () use ($device, $users, $employeeMap, &$linked): void {
            foreach ($users as $deviceUserId => $user) {
                $deviceUserId = (string) $deviceUserId;
                $name = trim($user['name']) ?: null;
                $employeeId = $employeeMap[$deviceUserId] ?? null;

                $data = [];

                if ($name !== null) {
                    $data['device_user_name'] = $name;
                }

                if ($employeeId !== null) {
                    $data['employee_id'] = $employeeId;
                }

                if ($data === []) {
                    continue;
                }

                $linked += AttendanceLog::query()
                    ->withoutGlobalScope(BranchScope::class)
                    ->where('biometric_device_id', $device->id)
                    ->where('device_user_id', $deviceUserId)
                    ->update($data);
            }

            $device->forceFill(['last_synced_at' => Carbon::now()])->save();
        });

        return ['fetched' => $fetched, 'linked' => $linked];
    }

    /**
     * device_user_id => enrolled name, from the terminal's user roster.
     * Best effort: returns an empty map if the device won't answer.
     *
     * @return array<string, string>
     */
    private function deviceUserNames(BiometricDevice $device): array
    {
        try {
            $users = $this->zkteco->enrollUsersMap($device);
        } catch (\Throwable) {
            return [];
        }

        $names = [];

        foreach ($users as $deviceUserId => $user) {
            $name = trim($user['name']);

            if ($name !== '') {
                $names[(string) $deviceUserId] = $name;
            }
        }

        return $names;
    }

    /**
     * biometric_id => employee id, for the device's branch. Bypasses
     * BranchScope so a web-triggered sync (which carries an active branch)
     * still sees every employee of the device's branch.
     *
     * @return Collection<string, int>
     */
    private function employeeMap(BiometricDevice $device): Collection
    {
        return Employee::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $device->branch_id)
            ->whereNotNull('biometric_id')
            ->pluck('id', 'biometric_id');
    }
}

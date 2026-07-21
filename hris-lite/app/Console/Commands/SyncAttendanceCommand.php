<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Services\AttendanceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class SyncAttendanceCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'attendance:sync
        {device? : Biometric device id to sync (defaults to every active device)}';

    /**
     * @var string
     */
    protected $description = 'Pull new punches from biometric device(s), map them to employees and store them.';

    public function handle(AttendanceSyncService $syncer): int
    {
        $devices = $this->resolveDevices();

        // null => a specific device was requested but does not exist (hard error).
        if ($devices === null) {
            return self::FAILURE;
        }

        if ($devices->isEmpty()) {
            $this->components->warn('No devices to sync.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($devices as $device) {
            try {
                // syncTime also labels punches with device-side names.
                $time = $syncer->syncTime($device);

                $this->components->info(sprintf(
                    '[%s] %d pulled, %d new, %d duplicate, %d unmatched.',
                    $device->name,
                    $time['pulled'],
                    $time['new'],
                    $time['duplicates'],
                    $time['unmatched'],
                ));
            } catch (Throwable $e) {
                $failed++;
                $this->components->error("[{$device->name}] {$e->getMessage()}");
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * The devices to sync, or null when a specific device was requested but not
     * found (so the caller can exit with a failure code).
     *
     * @return Collection<int, BiometricDevice>|null
     */
    private function resolveDevices(): ?Collection
    {
        $id = $this->argument('device');

        if ($id !== null) {
            $device = BiometricDevice::find($id);

            if ($device === null) {
                $this->components->error("Device [{$id}] not found.");

                return null;
            }

            return collect([$device]);
        }

        return BiometricDevice::query()->active()->orderBy('id')->get();
    }
}

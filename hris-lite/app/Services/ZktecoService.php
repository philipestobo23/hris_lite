<?php

namespace App\Services;

use App\Models\BiometricDevice;
use Illuminate\Support\Carbon;
use Jmrashed\Zkteco\Lib\ZKTeco;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around the ZKTeco UDP protocol library that speaks in terms of
 * our {@see BiometricDevice} model and returns normalised, framework-friendly
 * data (Carbon timestamps, in/out status, verify method) rather than the raw
 * device tuples.
 */
class ZktecoService
{
    /**
     * ZK verify-method code (a punch's "state") to our stored label.
     *
     * @var array<int, string>
     */
    private const VERIFY_MODES = [
        0 => 'password',
        1 => 'fingerprint',
        2 => 'card',
    ];

    /**
     * ZK attendance-type code (a punch's "type") to an in/out direction.
     *
     * @var array<int, string>
     */
    private const PUNCH_STATUS = [
        0 => 'in',   // check-in
        1 => 'out',  // check-out
        4 => 'in',   // overtime-in
        5 => 'out',  // overtime-out
    ];

    /**
     * Whether the device answers on its IP/port. Never throws: a device that is
     * offline or misconfigured simply reports false.
     */
    public function testConnection(BiometricDevice $device): bool
    {
        if (blank($device->ip_address)) {
            return false;
        }

        $client = null;

        try {
            $client = $this->makeClient($device);

            return $client->connect() !== false;
        } catch (Throwable) {
            return false;
        } finally {
            $this->safeDisconnect($client);
        }
    }

    /**
     * Connect and, on success, play the terminal's built-in confirmation voice
     * ("Thank you" — ZKTeco voice index 0) so the operator gets audible proof
     * at the device itself. Never throws; returns whether the device answered.
     */
    public function testConnectionWithVoice(BiometricDevice $device): bool
    {
        if (blank($device->ip_address)) {
            return false;
        }

        $client = null;

        try {
            $client = $this->makeClient($device);

            if ($client->connect() === false) {
                return false;
            }

            // Best-effort: a reachable device should greet the operator even if
            // this particular firmware ignores the voice command.
            $client->testVoice();

            return true;
        } catch (Throwable) {
            return false;
        } finally {
            $this->safeDisconnect($client);
        }
    }

    /**
     * Pull every attendance record stored on the device, normalised for
     * insertion into `attendance_logs`.
     *
     * @return list<array{device_user_id: string, punched_at: Carbon, status: string|null, verify_mode: string|null}>
     *
     * @throws RuntimeException when the device cannot be reached.
     */
    public function pullAttendanceLogs(BiometricDevice $device): array
    {
        return $this->onConnected($device, function (ZKTeco $client): array {
            $logs = [];

            foreach ($client->getAttendance() as $record) {
                $deviceUserId = $this->deviceUserId($record);
                $timestamp = $record['timestamp'] ?? null;

                if ($deviceUserId === '' || blank($timestamp)) {
                    continue; // unusable row — no user or no time
                }

                $logs[] = [
                    'device_user_id' => $deviceUserId,
                    'punched_at' => Carbon::parse($timestamp),
                    'status' => self::PUNCH_STATUS[$record['type'] ?? -1] ?? null,
                    'verify_mode' => self::VERIFY_MODES[$record['state'] ?? -1] ?? null,
                ];
            }

            return $logs;
        });
    }

    /**
     * Map of the users enrolled on the device, keyed by their device user id
     * (the enrollment number matched against `employees.biometric_id`).
     *
     * @return array<string, array{uid: int, name: string, role: int, cardno: string|null}>
     *
     * @throws RuntimeException when the device cannot be reached.
     */
    public function enrollUsersMap(BiometricDevice $device): array
    {
        return $this->onConnected($device, function (ZKTeco $client): array {
            $map = [];

            foreach ($client->getUser() as $user) {
                $deviceUserId = $this->deviceUserId($user);

                if ($deviceUserId === '') {
                    continue;
                }

                $map[$deviceUserId] = [
                    'uid' => (int) ($user['uid'] ?? 0),
                    'name' => trim((string) ($user['name'] ?? '')),
                    'role' => (int) ($user['role'] ?? 0),
                    'cardno' => isset($user['cardno']) ? trim((string) $user['cardno']) : null,
                ];
            }

            return $map;
        });
    }

    /**
     * Run a callback with an open device connection, disconnecting afterwards.
     *
     * @template T
     *
     * @param  callable(ZKTeco): T  $callback
     * @return T
     *
     * @throws RuntimeException when the device cannot be reached.
     */
    private function onConnected(BiometricDevice $device, callable $callback)
    {
        if (blank($device->ip_address)) {
            throw new RuntimeException("Device [{$device->name}] has no IP address configured.");
        }

        $client = null;

        try {
            $client = $this->makeClient($device);

            if ($client->connect() === false) {
                throw new RuntimeException("Could not connect to device [{$device->name}] at {$device->ip_address}:{$device->port}.");
            }

            return $callback($client);
        } finally {
            $this->safeDisconnect($client);
        }
    }

    /**
     * Resolve the device-side user id from a record, preferring the enrolled
     * user id (badge) and falling back to the internal uid.
     *
     * @param  array<string, mixed>  $record
     */
    private function deviceUserId(array $record): string
    {
        $id = trim((string) ($record['id'] ?? $record['userid'] ?? ''));

        if ($id !== '') {
            return $id;
        }

        return isset($record['uid']) ? (string) $record['uid'] : '';
    }

    /**
     * Build the underlying protocol client. Isolated so it can be swapped in
     * tests without touching a real socket.
     */
    protected function makeClient(BiometricDevice $device): ZKTeco
    {
        return new ZKTeco($device->ip_address, $device->port);
    }

    private function safeDisconnect(?ZKTeco $client): void
    {
        if ($client === null) {
            return;
        }

        try {
            $client->disconnect();
        } catch (Throwable) {
            // Best-effort cleanup; nothing actionable if teardown fails.
        }
    }
}

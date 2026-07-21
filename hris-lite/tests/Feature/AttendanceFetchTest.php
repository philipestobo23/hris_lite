<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Services\ZktecoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceFetchTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_device_returns_new_count_and_stores_punches(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $branch = Branch::create(['name' => 'HQ', 'is_active' => true]);
        $device = BiometricDevice::create([
            'branch_id' => $branch->id, 'name' => 'Lobby', 'model' => 'K40',
            'ip_address' => '10.0.0.1', 'port' => 4370, 'mode' => 'push', 'is_active' => true,
        ]);
        Employee::create([
            'branch_id' => $branch->id, 'first_name' => 'Test', 'last_name' => 'User',
            'biometric_id' => '5', 'employment_status' => 'probationary',
        ]);

        $this->app->instance(ZktecoService::class, new class extends ZktecoService
        {
            public function pullAttendanceLogs(BiometricDevice $device): array
            {
                return [
                    ['device_user_id' => '5', 'punched_at' => Carbon::parse('2026-07-21 08:00:00'), 'status' => 'in', 'verify_mode' => 'fingerprint'],
                    ['device_user_id' => '9', 'punched_at' => Carbon::parse('2026-07-21 08:01:00'), 'status' => 'in', 'verify_mode' => 'card'],
                ];
            }

            public function enrollUsersMap(BiometricDevice $device): array
            {
                return [
                    '5' => ['uid' => 5, 'name' => 'Test User', 'role' => 0, 'cardno' => null],
                    '9' => ['uid' => 9, 'name' => 'Walk-in Guard', 'role' => 0, 'cardno' => null],
                ];
            }
        });

        // 1. Fetch the user roster first (stores names + employee mapping).
        $this->actingAs($user)
            ->postJson(route('biometric-users.fetch-device', $device))
            ->assertOk()
            ->assertJson(['ok' => true, 'fetched' => 2, 'mapped' => 1]);

        $this->assertDatabaseCount('device_users', 2);
        $this->assertDatabaseHas('device_users', [
            'device_user_id' => '9',
            'name' => 'Walk-in Guard',
            'employee_id' => null,
        ]);

        // 2. Fetch the punches — names come from the stored roster.
        $this->actingAs($user)
            ->postJson(route('attendance-logs.fetch-time', $device))
            ->assertOk()
            ->assertJson(['ok' => true, 'new' => 2, 'unmatched' => 1]);

        $this->assertDatabaseCount('attendance_logs', 2);
        $this->assertDatabaseHas('attendance_logs', [
            'device_user_id' => '9',
            'device_user_name' => 'Walk-in Guard',
            'employee_id' => null,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
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

        // Fetch time: punches land in attendance_logs, already labelled with
        // the device-side name and linked to the matching employee.
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
        $this->assertDatabaseHas('attendance_logs', [
            'device_user_id' => '5',
            'device_user_name' => 'Test User',
        ]);
    }

    public function test_fetch_users_refreshes_names_and_employee_links_on_punches(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $branch = Branch::create(['name' => 'HQ', 'is_active' => true]);
        $device = BiometricDevice::create([
            'branch_id' => $branch->id, 'name' => 'Lobby', 'model' => 'K40',
            'ip_address' => '10.0.0.1', 'port' => 4370, 'mode' => 'push', 'is_active' => true,
        ]);

        // A punch stored with no name and no employee link yet.
        AttendanceLog::create([
            'branch_id' => $branch->id,
            'biometric_device_id' => $device->id,
            'device_user_id' => '5',
            'punched_at' => Carbon::parse('2026-07-21 08:00:00'),
            'status' => 'in',
        ]);

        // The employee is registered in the system afterwards.
        $employee = Employee::create([
            'branch_id' => $branch->id, 'first_name' => 'Test', 'last_name' => 'User',
            'biometric_id' => '5', 'employment_status' => 'probationary',
        ]);

        $this->app->instance(ZktecoService::class, new class extends ZktecoService
        {
            public function enrollUsersMap(BiometricDevice $device): array
            {
                return ['5' => ['uid' => 5, 'name' => 'Test User', 'role' => 0, 'cardno' => null]];
            }
        });

        $this->actingAs($user)
            ->postJson(route('attendance-logs.fetch-users', $device))
            ->assertOk()
            ->assertJson(['ok' => true, 'fetched' => 1, 'linked' => 1]);

        $this->assertDatabaseHas('attendance_logs', [
            'device_user_id' => '5',
            'device_user_name' => 'Test User',
            'employee_id' => $employee->id,
        ]);
    }
}

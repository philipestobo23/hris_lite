<?php

use App\Http\Controllers\ActiveBranchController;
use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('branches', BranchController::class)->except('show');
    Route::resource('departments', DepartmentController::class)->except('show');
    Route::resource('positions', PositionController::class)->except('show');
    // Registered before the resource so it isn't captured by employees/{employee}.
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::patch('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
        ->name('employees.toggle-status');
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])
        ->name('employees.documents.store');
    Route::delete('employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'destroy'])
        ->name('employees.documents.destroy')
        ->scopeBindings();

    Route::post('biometric-devices/{biometric_device}/test-connection', [BiometricDeviceController::class, 'testConnection'])
        ->name('biometric-devices.test-connection');
    Route::resource('biometric-devices', BiometricDeviceController::class)->except('show');

    Route::get('attendance-logs', [AttendanceLogController::class, 'index'])->name('attendance-logs.index');
    Route::post('attendance-logs/devices/{biometric_device}/fetch-users', [AttendanceLogController::class, 'fetchUsersDevice'])
        ->name('attendance-logs.fetch-users');
    Route::post('attendance-logs/devices/{biometric_device}/fetch-time', [AttendanceLogController::class, 'fetchTimeDevice'])
        ->name('attendance-logs.fetch-time');

    Route::resource('roles', RoleController::class)->except('show');

    Route::get('app-settings', [SettingsController::class, 'edit'])->name('app-settings.edit');
    Route::post('app-settings', [SettingsController::class, 'update'])->name('app-settings.update');

    Route::post('active-branch', [ActiveBranchController::class, 'update'])->name('active-branch.update');
});

require __DIR__.'/settings.php';

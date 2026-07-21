<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            // Branch-scoped like every other operational record.
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('biometric_device_id')->nullable()->constrained()->nullOnDelete();
            // Raw punches can arrive before a device user is mapped to an employee.
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();

            // Enrollment number stored on the terminal (the device's own user id).
            $table->string('device_user_id');
            $table->timestamp('punched_at');
            // Direction of the punch (in/out); left null when the device is unset.
            $table->string('status')->nullable();
            // How the identity was verified: fingerprint, face, card, password.
            $table->string('verify_mode')->nullable();
            $table->string('work_code')->nullable();
            // Whether this raw punch has been rolled up into a processed record.
            $table->boolean('is_processed')->default(false);

            $table->timestamps();

            $table->index(['branch_id', 'punched_at']);
            $table->index(['employee_id', 'punched_at']);
            // A terminal never reports the same user at the same instant twice;
            // makes device sync idempotent.
            $table->unique(['biometric_device_id', 'device_user_id', 'punched_at'], 'attendance_logs_punch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};

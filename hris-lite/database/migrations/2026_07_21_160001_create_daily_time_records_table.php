<?php

use App\Enums\DtrStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_time_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            // Set when the day falls on a holiday, so payroll can apply its pay rule.
            $table->foreignId('holiday_id')->nullable()->constrained()->nullOnDelete();

            $table->date('work_date');

            // Resolved from the raw punches in `attendance_logs`.
            $table->timestamp('time_in')->nullable();
            $table->timestamp('break_out')->nullable();
            $table->timestamp('break_in')->nullable();
            $table->timestamp('time_out')->nullable();

            $table->decimal('hours_worked', 5, 2)->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('undertime_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->unsignedSmallInteger('night_differential_minutes')->default(0);

            $table->boolean('is_rest_day')->default(false);
            $table->string('status')->default(DtrStatus::Absent->value);
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // One record per employee per day.
            $table->unique(['employee_id', 'work_date']);
            $table->index(['branch_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_time_records');
    }
};

<?php

use App\Enums\LeaveStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 4, 1)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->text('reason')->nullable();

            $table->string('status')->default(LeaveStatus::Pending->value);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Look-ups are "does this employee have an approved leave covering
            // this date", so index the employee + range together.
            $table->index(['employee_id', 'start_date', 'end_date']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};

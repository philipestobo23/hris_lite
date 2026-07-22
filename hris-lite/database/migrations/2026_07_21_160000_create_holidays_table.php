<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();

            // Branch scope: NULL = nationwide/company-wide (every branch),
            // otherwise the holiday only applies to that branch (e.g. a local
            // town fiesta).
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->string('name');
            $table->string('type');
            // Multiplier applied to hours worked on this day, e.g. 2.00 = 200%.
            $table->decimal('pay_rule', 5, 2)->default(1.00);

            $table->timestamps();
            $table->softDeletes();

            // One entry per date per branch. Note MySQL treats each NULL as
            // distinct, so this does not stop two company-wide holidays on the
            // same date — HolidayRequest enforces that case in validation.
            $table->unique(['date', 'branch_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};

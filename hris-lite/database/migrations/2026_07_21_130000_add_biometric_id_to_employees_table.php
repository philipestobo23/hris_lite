<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // The enrollment number this employee is registered under on the
            // biometric terminals. Matched against a punch's device_user_id
            // during attendance sync. Not globally unique: different branches
            // may reuse the same short enrollment numbers on their own devices.
            $table->string('biometric_id')->nullable()->after('employee_no');
            $table->index(['branch_id', 'biometric_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'biometric_id']);
            $table->dropColumn('biometric_id');
        });
    }
};

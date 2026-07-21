<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Personal
            $table->string('civil_status')->nullable()->after('gender');
            $table->string('nationality')->nullable()->after('civil_status');

            // Emergency contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Government IDs
            $table->string('sss_no')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('philhealth_no')->nullable();
            $table->string('pagibig_no')->nullable();

            // Salary
            $table->string('salary_type')->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->decimal('allowance', 12, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'civil_status',
                'nationality',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
                'sss_no',
                'tin_no',
                'philhealth_no',
                'pagibig_no',
                'salary_type',
                'basic_salary',
                'allowance',
                'bank_name',
                'bank_account_no',
            ]);
        });
    }
};

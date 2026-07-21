<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            // The name enrolled on the terminal for this device_user_id, pulled
            // from the device roster during sync. Lets the UI identify a person
            // even before the punch is linked to an employee record.
            $table->string('device_user_name')->nullable()->after('device_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn('device_user_name');
        });
    }
};

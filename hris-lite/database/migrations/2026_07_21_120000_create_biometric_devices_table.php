<?php

use App\Enums\DeviceMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();

            // Each device physically lives at a branch, so it is branch-scoped.
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->string('model');
            $table->string('ip_address')->nullable();
            $table->unsignedInteger('port')->default(4370);
            $table->string('serial_number')->nullable()->unique();
            $table->string('mode')->default(DeviceMode::Push->value);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_devices');
    }
};

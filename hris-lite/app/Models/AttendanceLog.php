<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single raw punch pulled from (or pushed by) a biometric terminal.
 *
 * @property int $id
 * @property int $branch_id
 * @property int|null $biometric_device_id
 * @property int|null $employee_id
 * @property string $device_user_id
 * @property string|null $device_user_name
 * @property Carbon $punched_at
 * @property string|null $status
 * @property string|null $verify_mode
 * @property string|null $work_code
 * @property bool $is_processed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BiometricDevice|null $device
 * @property-read Employee|null $employee
 */
#[Fillable([
    'branch_id',
    'biometric_device_id',
    'employee_id',
    'device_user_id',
    'device_user_name',
    'punched_at',
    'status',
    'verify_mode',
    'work_code',
    'is_processed',
])]
class AttendanceLog extends Model
{
    use BelongsToBranch;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'punched_at' => 'datetime',
            'is_processed' => 'boolean',
        ];
    }

    public function branchScopeAllowsShared(): bool
    {
        return false;
    }

    /**
     * @return BelongsTo<BiometricDevice, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

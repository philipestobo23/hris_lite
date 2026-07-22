<?php

namespace App\Models;

use App\Enums\DtrStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One resolved working day per employee, derived from the raw punches in
 * `attendance_logs` and used as the basis for payroll.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $employee_id
 * @property int|null $holiday_id
 * @property Carbon $work_date
 * @property Carbon|null $time_in
 * @property Carbon|null $break_out
 * @property Carbon|null $break_in
 * @property Carbon|null $time_out
 * @property string $hours_worked
 * @property int $late_minutes
 * @property int $undertime_minutes
 * @property int $overtime_minutes
 * @property int $night_differential_minutes
 * @property bool $is_rest_day
 * @property DtrStatus $status
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Employee $employee
 * @property-read Holiday|null $holiday
 */
#[Fillable([
    'branch_id',
    'employee_id',
    'holiday_id',
    'work_date',
    'time_in',
    'break_out',
    'break_in',
    'time_out',
    'hours_worked',
    'late_minutes',
    'undertime_minutes',
    'overtime_minutes',
    'night_differential_minutes',
    'is_rest_day',
    'status',
    'remarks',
])]
class DailyTimeRecord extends Model
{
    use BelongsToBranch, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'time_in' => 'datetime',
            'break_out' => 'datetime',
            'break_in' => 'datetime',
            'time_out' => 'datetime',
            'hours_worked' => 'decimal:2',
            'late_minutes' => 'integer',
            'undertime_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'night_differential_minutes' => 'integer',
            'is_rest_day' => 'boolean',
            'status' => DtrStatus::class,
        ];
    }

    public function branchScopeAllowsShared(): bool
    {
        return false;
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Holiday, $this>
     */
    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }
}

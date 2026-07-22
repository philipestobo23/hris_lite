<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An employee's leave filing. Approved leaves override the status of the
 * daily time records they cover.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $employee_id
 * @property LeaveType $type
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $days
 * @property bool $is_paid
 * @property string|null $reason
 * @property LeaveStatus $status
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Employee $employee
 */
#[Fillable([
    'branch_id',
    'employee_id',
    'type',
    'start_date',
    'end_date',
    'days',
    'is_paid',
    'reason',
    'status',
    'approved_by',
    'approved_at',
])]
class Leave extends Model
{
    use BelongsToBranch, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LeaveType::class,
            'status' => LeaveStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:1',
            'is_paid' => 'boolean',
            'approved_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @param  Builder<Leave>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', LeaveStatus::Approved);
    }

    /**
     * Leaves whose inclusive date range covers the given day.
     *
     * @param  Builder<Leave>  $query
     */
    public function scopeCovering(Builder $query, string $date): void
    {
        $query->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }
}

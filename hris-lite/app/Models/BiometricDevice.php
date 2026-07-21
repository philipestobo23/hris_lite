<?php

namespace App\Models;

use App\Enums\DeviceMode;
use App\Enums\DeviceModel;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property DeviceModel $model
 * @property string|null $ip_address
 * @property int $port
 * @property string|null $serial_number
 * @property DeviceMode $mode
 * @property bool $is_active
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Branch $branch
 */
#[Fillable([
    'branch_id',
    'name',
    'model',
    'ip_address',
    'port',
    'serial_number',
    'mode',
    'is_active',
])]
class BiometricDevice extends Model
{
    // A device belongs to exactly one branch (branch_id is required), so the
    // shared-row behaviour of the trait never applies here.
    use BelongsToBranch, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'model' => DeviceModel::class,
            'mode' => DeviceMode::class,
            'is_active' => 'boolean',
            'port' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function branchScopeAllowsShared(): bool
    {
        return false;
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<AttendanceLog, $this>
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * @param  Builder<BiometricDevice>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

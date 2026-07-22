<?php

namespace App\Models;

use App\Enums\HolidayType;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $branch_id
 * @property Carbon $date
 * @property string $name
 * @property HolidayType $type
 * @property string $pay_rule
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Branch|null $branch
 */
#[Fillable(['branch_id', 'date', 'name', 'type', 'pay_rule'])]
class Holiday extends Model
{
    // branch_id is nullable => a NULL holiday is company-wide (shared) and
    // visible to every branch. Uses the trait defaults.
    use BelongsToBranch, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => HolidayType::class,
            'pay_rule' => 'decimal:2',
        ];
    }

    /**
     * The branch this holiday is limited to. Null means it applies company-wide.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Holidays that apply to a given branch: its own plus the company-wide ones.
     *
     * @param  Builder<Holiday>  $query
     */
    public function scopeForBranch(Builder $query, int $branchId): void
    {
        $query->where(fn (Builder $inner) => $inner->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }

    /**
     * @param  Builder<Holiday>  $query
     */
    public function scopeBetween(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('date', [$from, $to]);
    }
}

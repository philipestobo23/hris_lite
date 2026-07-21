<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\SalaryType;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $employee_no
 * @property string|null $biometric_id
 * @property int $branch_id
 * @property int|null $department_id
 * @property int|null $position_id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $photo
 * @property EmploymentStatus $employment_status
 * @property Carbon|null $hire_date
 * @property Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $full_name
 * @property-read string|null $photo_url
 */
#[Fillable([
    'employee_no',
    'biometric_id',
    'branch_id',
    'department_id',
    'position_id',
    'first_name',
    'middle_name',
    'last_name',
    'email',
    'phone',
    'photo',
    'employment_status',
    'hire_date',
    'date_of_birth',
    'gender',
    'civil_status',
    'nationality',
    'address',
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
])]
class Employee extends Model
{
    use BelongsToBranch, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $appends = ['full_name', 'photo_url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_status' => EmploymentStatus::class,
            'salary_type' => SalaryType::class,
            'basic_salary' => 'decimal:2',
            'allowance' => 'decimal:2',
            'hire_date' => 'date',
            'date_of_birth' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            if (blank($employee->employee_no)) {
                $employee->employee_no = static::nextEmployeeNo();
            }
        });
    }

    /**
     * Next sequential employee number, e.g. "EMP-0001". Ignores global scopes
     * so numbering is unique across every branch, not per active branch.
     */
    public static function nextEmployeeNo(): string
    {
        $max = (int) static::withoutGlobalScopes()
            ->where('employee_no', 'like', 'EMP-%')
            ->selectRaw('MAX(CAST(SUBSTRING(employee_no, 5) AS UNSIGNED)) as max_no')
            ->value('max_no');

        return 'EMP-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Apply the master-list filters (search + branch/department/status).
     *
     * @param  Builder<Employee>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $q->where(function (Builder $inner) use ($search): void {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_no', 'like', "%{$search}%");
                });
            })
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $value) => $q->where('branch_id', $value))
            ->when($filters['department_id'] ?? null, fn (Builder $q, $value) => $q->where('department_id', $value))
            ->when($filters['status'] ?? null, fn (Builder $q, $value) => $q->where('employment_status', $value));
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return HasMany<EmployeeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->photo ? Storage::disk('public')->url($this->photo) : null,
        );
    }
}

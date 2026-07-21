<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $employee_id
 * @property string $name
 * @property string|null $type
 * @property string $file_path
 * @property string|null $file_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string|null $url
 */
#[Fillable(['employee_id', 'name', 'type', 'file_path', 'file_name'])]
class EmployeeDocument extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $appends = ['url'];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
        );
    }
}

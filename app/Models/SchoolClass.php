<?php

namespace App\Models;

use App\Observers\SchoolStructureObserver;
use Database\Factories\SchoolClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A school class such as "10Б". Named SchoolClass because "Class" is reserved in PHP.
 */
#[ObservedBy(SchoolStructureObserver::class)]
#[Fillable(['grade_id', 'letter', 'profile_id'])]
class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
    use HasFactory;

    protected $table = 'classes';

    protected $with = ['grade'];

    /**
     * @return BelongsTo<Grade, $this>
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }

    /**
     * @return HasMany<AllowedStudent, $this>
     */
    public function allowedStudents(): HasMany
    {
        return $this->hasMany(AllowedStudent::class, 'class_id');
    }

    /**
     * @param  Builder<SchoolClass>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->join('grades', 'grades.id', '=', 'classes.grade_id')
            ->orderBy('grades.number')
            ->orderBy('classes.letter')
            ->select('classes.*');
    }

    /**
     * Display name, e.g. "10Б".
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->grade?->number.$this->letter);
    }

    protected function letter(): Attribute
    {
        return Attribute::set(fn (string $value) => mb_strtoupper(trim($value)));
    }
}

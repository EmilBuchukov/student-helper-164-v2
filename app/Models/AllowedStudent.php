<?php

namespace App\Models;

use Database\Factories\AllowedStudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin-imported whitelist entry: only these emails may register as students.
 */
#[Fillable(['email', 'class_id', 'claimed_at', 'claimed_by'])]
class AllowedStudent extends Model
{
    /** @use HasFactory<AllowedStudentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    /**
     * @param  Builder<AllowedStudent>  $query
     */
    public function scopeUnclaimed(Builder $query): void
    {
        $query->whereNull('claimed_at');
    }

    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value) => mb_strtolower(trim($value)));
    }

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
        ];
    }
}

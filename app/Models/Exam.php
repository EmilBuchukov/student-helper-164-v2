<?php

namespace App\Models;

use App\Enums\ExamType;
use App\Models\Concerns\HasAudienceScope;
use App\Services\MaterialStorage;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject', 'title', 'description', 'exam_date', 'type', 'scope_type', 'scope_id', 'created_by'])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasAudienceScope, HasFactory;

    protected static function booted(): void
    {
        // Database cascade removes the rows; the stored files must be removed explicitly.
        static::deleting(function (Exam $exam) {
            $exam->materials()->each(fn (Material $material) => app(MaterialStorage::class)->delete($material));
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Material, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function isUpcoming(): bool
    {
        return $this->exam_date->isToday() || $this->exam_date->isFuture();
    }

    /**
     * Teachers may browse exams of every class (but not Q&A or channels).
     */
    protected static function audienceSeesAll(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator() || $user->isTeacher();
    }

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'type' => ExamType::class,
        ];
    }
}

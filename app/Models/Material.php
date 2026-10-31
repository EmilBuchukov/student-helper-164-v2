<?php

namespace App\Models;

use App\Services\MaterialStorage;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A study file attached to an exam. Files live on the configured disk under a random name
 * and are only reachable through MaterialController::download (authorization + original name).
 */
#[Fillable(['exam_id', 'user_id', 'title', 'file_path', 'original_name', 'mime_type', 'size'])]
#[Hidden(['file_path'])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(fn (Material $material) => app(MaterialStorage::class)->delete($material));
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Material;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stores exam materials on the configured disk (FILESYSTEM_DISK: local by default, s3 in production).
 * Files get a random name; the original name is kept only in the database.
 */
class MaterialStorage
{
    public const DIRECTORY = 'materials';

    public function disk(): Filesystem
    {
        return Storage::disk(config('school.materials_disk') ?: config('filesystems.default'));
    }

    public function store(Exam $exam, User $user, UploadedFile $file, string $title): Material
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = self::DIRECTORY.'/'.$exam->id.'/'.Str::random(40).'.'.$extension;

        if (! $this->disk()->put($path, $file->getContent())) {
            throw new RuntimeException('Could not store the uploaded material.');
        }

        return $exam->materials()->create([
            'user_id' => $user->id,
            'title' => $title,
            'file_path' => $path,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);
    }

    public function download(Material $material): StreamedResponse
    {
        abort_unless($this->disk()->exists($material->file_path), 404);

        return $this->disk()->download($material->file_path, $material->original_name, [
            'Content-Type' => $material->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(Material $material): void
    {
        $this->disk()->delete($material->file_path);
    }
}

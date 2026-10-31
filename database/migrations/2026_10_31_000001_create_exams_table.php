<?php

use App\Enums\ExamType;
use App\Enums\ScopeType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 100);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('exam_date');
            $table->enum('type', ExamType::values());
            $table->enum('scope_type', ScopeType::values());
            $table->unsignedBigInteger('scope_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
            $table->index('exam_date');
            $table->index('subject');
            $table->index('created_by');
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index('exam_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
        Schema::dropIfExists('exams');
    }
};

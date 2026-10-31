<?php

use App\Enums\ReportStatus;
use App\Enums\ScopeType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->text('body');
            $table->string('subject', 100)->nullable();
            // Null scope = school-wide question.
            $table->enum('scope_type', ScopeType::values())->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->unsignedBigInteger('accepted_answer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scope_type', 'scope_id']);
            $table->index('subject');
            $table->index('created_at');
            $table->index('accepted_answer_id');
            $table->index('user_id');
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['question_id', 'created_at']);
            $table->index('user_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('accepted_answer_id')->references('id')->on('answers')->nullOnDelete();
        });

        // Upvotes only; a row exists while the vote is active (toggle = insert/delete).
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('votable');
            $table->timestamps();

            $table->unique(['user_id', 'votable_type', 'votable_id']);
        });

        // Shared by questions, answers and channel messages.
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('reportable');
            $table->text('reason');
            $table->enum('status', ReportStatus::values())->default(ReportStatus::Open->value);
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // At most one open report per user and item.
        DB::statement("CREATE UNIQUE INDEX reports_one_open_per_user ON reports (reporter_id, reportable_type, reportable_id) WHERE status = 'open'");
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('votes');
        Schema::table('questions', fn (Blueprint $table) => $table->dropForeign(['accepted_answer_id']));
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
    }
};

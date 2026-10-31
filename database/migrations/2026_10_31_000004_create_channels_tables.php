<?php

use App\Enums\ScopeType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->enum('scope_type', ScopeType::values());
            $table->unsignedBigInteger('scope_id');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            // Polling (id > last) and "load more" (id < first) per channel.
            $table->index(['channel_id', 'id']);
            $table->index(['channel_id', 'deleted_at']);
            $table->index('user_id');
        });

        Schema::create('channel_mutes', function (Blueprint $table) {
            $table->id();
            // Null channel = muted in every channel.
            $table->foreignId('channel_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('muted_until');
            $table->string('reason', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'muted_until']);
            $table->index(['channel_id', 'muted_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_mutes');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('channels');
    }
};

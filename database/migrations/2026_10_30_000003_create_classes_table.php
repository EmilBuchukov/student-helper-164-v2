<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades')->restrictOnDelete();
            $table->string('letter', 2);
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->timestamps();

            $table->unique(['grade_id', 'letter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};

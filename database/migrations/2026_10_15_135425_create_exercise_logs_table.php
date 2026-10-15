<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exercise_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_exercise_id')->constrained()->cascadeOnDelete();
            $table->timestamp('performed_at');
            $table->unsignedSmallInteger('sets_done')->default(0);
            $table->unsignedSmallInteger('reps_done')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('pain_score')->nullable(); // 0-10
            $table->unsignedTinyInteger('effort')->nullable();     // 1-10
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_logs');
    }
};

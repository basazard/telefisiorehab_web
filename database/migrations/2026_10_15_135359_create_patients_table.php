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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('therapist_id')->nullable()->constrained('users')->nullOnDelete();

            // Step 1: identity (name comes from users.name)
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();

            // Step 2: physical data
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('bmi', 4, 1)->nullable();

            // Step 3: occupation and activity
            $table->string('occupation')->nullable();
            $table->decimal('activity_hours_per_day', 3, 1)->nullable();

            // Step 5 and 6
            $table->boolean('has_special_condition')->default(false);
            $table->json('special_conditions')->nullable();
            $table->string('special_condition_other')->nullable();
            $table->json('goals')->nullable();
            $table->string('goal_other')->nullable();

            $table->text('notes')->nullable();            // therapist notes
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};

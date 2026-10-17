<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per screening "episode". A re-assessment is a NEW row, so history is kept.
        Schema::create('screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->json('regions');                       // ["Lower Back", "Left Knee"]
            $table->json('complaints');                    // {"Left Knee": {onset, onsetUnknown, onsetType, characters[], aggravators[], nrs}}
            $table->unsignedTinyInteger('max_pain')->default(0);
            $table->timestamp('completed_at')->nullable(); // null = still in progress
            $table->timestamps();

            $table->index(['patient_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screenings');
    }
};

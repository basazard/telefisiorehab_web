<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('screening_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->json('answers');                                  // lefs-0..19, odi-0..9, rom
            $table->unsignedTinyInteger('lefs');                      // 0-80
            $table->unsignedTinyInteger('odi')->nullable();           // 0-50, only when a "back" region was chosen
            $table->unsignedTinyInteger('odi_percent')->nullable();
            $table->unsignedSmallInteger('rom')->default(0);
            $table->unsignedTinyInteger('pain')->default(0);          // max NRS at the time
            $table->string('tier');                                   // low | moderate | high
            $table->string('domain');                                 // snapshot of pickDomain()
            $table->string('track')->nullable();                      // snapshot: pediatric | adult | geriatric
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Who opened or changed a patient's data (PRD 09 S2.1). Append only: no updated_at. */
    public function up(): void
    {
        Schema::create('patient_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('action');                       // viewed_detail | assigned | note_added | program changes (S3)
            $table->timestamp('created_at')->nullable();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_access_logs');
    }
};

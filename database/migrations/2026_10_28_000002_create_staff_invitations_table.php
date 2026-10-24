<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Invitations of new staff by a clinic admin (PRD 09 S4.2). Only a hash of the token is stored. */
    public function up(): void
    {
        Schema::create('staff_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default('therapist');
            $table->string('token_hash', 64)->unique();         // sha256 of the token in the link
            $table->timestamp('expires_at');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invitations');
    }
};

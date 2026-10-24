<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Program review by a physiotherapist and soft removal of items (PRD 09 S3.1). */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_type')->nullable();                 // approved | adjusted (the patient's badge)
            $table->text('review_note')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();   // null = ended by the system
        });

        Schema::table('program_exercises', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable();               // never delete an item: its logs would go too
        });
    }

    public function down(): void
    {
        Schema::table('program_exercises', fn (Blueprint $table) => $table->dropColumn('removed_at'));

        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('ended_by');
            $table->dropColumn(['reviewed_at', 'review_type', 'review_note']);
        });
    }
};

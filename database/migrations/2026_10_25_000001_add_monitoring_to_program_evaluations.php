<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Monitoring & Reassessment PRD: monitoring re-runs the domain instruments and compares them with the baseline.
        Schema::table('program_evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('function')->nullable()->change();     // replaced by the domain instruments
            $table->unsignedTinyInteger('mobility')->nullable()->change();
        });

        Schema::table('program_evaluations', function (Blueprint $table) {
            $table->json('answers')->nullable();                  // instrument key => answers (same shape as the assessment)
            $table->json('comparison')->nullable();               // item key => {label, before, after, change, direction, significant}
            $table->string('tier')->nullable();                   // risk category of the monitoring answers
            $table->text('side_effects')->nullable();
            $table->json('barriers')->nullable();                 // nyeri, motivasi, lingkungan, ...
            $table->string('outcome')->nullable();                // progress | modify | reassess | referral | maintenance
            $table->boolean('goal_achieved')->default(false);
        });

        // A maintenance program belongs to the same assessment as the program it follows.
        Schema::table('programs', function (Blueprint $table) {
            $table->index('assessment_id');
        });
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique(['assessment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unique('assessment_id');
        });
        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex(['assessment_id']);
        });

        Schema::table('program_evaluations', function (Blueprint $table) {
            $table->dropColumn(['answers', 'comparison', 'tier', 'side_effects', 'barriers', 'outcome', 'goal_achieved']);
        });
    }
};

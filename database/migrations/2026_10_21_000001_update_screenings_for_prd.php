<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenings', function (Blueprint $table) {
            // per-region cards are gone: only a 0-10 pain level per area (or 'overall') is kept per area
            $table->renameColumn('complaints', 'pain_levels');
        });

        Schema::table('screenings', function (Blueprint $table) {
            $table->json('main_complaints')->nullable();            // {"keys": ["pain","stiffness"], "other": null}
            $table->boolean('non_specific_location')->default(false);
            $table->json('characteristics')->nullable();            // onset, aggravators, relievers, yes/no answers, notes
            $table->json('special_groups')->nullable();             // [{"key":"geriatric","status":"pending"}]
            $table->string('status')->default('in_progress')->index(); // in_progress | stopped | completed
            $table->unsignedTinyInteger('current_step')->default(1);
        });

        Schema::table('red_flag_checks', function (Blueprint $table) {
            $table->dropColumn('acknowledged_at');                   // hard stop replaces "acknowledge"
        });

        Schema::table('red_flag_checks', function (Blueprint $table) {
            $table->string('other_text')->nullable();
            $table->timestamp('stopped_at')->nullable()->index();   // set when any answer is "yes"
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('unlock_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('red_flag_checks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unlocked_by');
            $table->dropColumn(['other_text', 'stopped_at', 'unlocked_at', 'unlock_note']);
            $table->timestamp('acknowledged_at')->nullable();
        });

        Schema::table('screenings', function (Blueprint $table) {
            $table->dropColumn(['main_complaints', 'non_specific_location', 'characteristics', 'special_groups', 'status', 'current_step']);
        });

        Schema::table('screenings', function (Blueprint $table) {
            $table->renameColumn('pain_levels', 'complaints');
        });
    }
};

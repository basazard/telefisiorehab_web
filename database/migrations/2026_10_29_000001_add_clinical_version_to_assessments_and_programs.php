<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The clinical rule set that produced a result (PRD 10 W5), e.g. "2026.1-draft". Null for older rows. */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('clinical_version', 32)->nullable();
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->string('clinical_version', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn('clinical_version'));
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn('clinical_version'));
    }
};

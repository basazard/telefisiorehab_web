<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scoring & Risk Stratification PRD: the overall category goes into the existing `tier` column.
        Schema::table('assessments', function (Blueprint $table) {
            $table->json('risk_details')->nullable();                 // item key => {label, display, tier}
            $table->timestamp('stratified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['risk_details', 'stratified_at']);
        });
    }
};

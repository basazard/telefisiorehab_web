<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clinical Assessment PRD: 7 domains with their own instruments, so LEFS and the legacy tier become optional.
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedTinyInteger('lefs')->nullable()->change();
            $table->string('tier')->nullable()->change();             // filled by "Klasifikasi Risiko" later
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->string('domain_reason')->nullable();              // why pickDomain() chose the domain
            $table->json('instruments')->nullable();                  // self-report instrument keys, in order
            $table->json('scores')->nullable();                       // instrument key => {value, display, limited, ...}
            $table->json('findings')->nullable();                     // limitations found (shown to the patient)
            $table->json('warnings')->nullable();
            $table->boolean('has_limitation')->nullable();            // "keterbatasan fungsi atau risiko?"
            $table->string('outcome')->nullable();                    // risk_classification | prevention_education
            $table->string('status')->default('in_progress')->index(); // in_progress | completed
            $table->unsignedTinyInteger('current_step')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['domain_reason', 'instruments', 'scores', 'findings', 'warnings', 'has_limitation', 'outcome', 'status', 'current_step']);
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedTinyInteger('lefs')->nullable(false)->change();
            $table->string('tier')->nullable(false)->change();
        });
    }
};

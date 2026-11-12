<?php

use App\Models\Clinic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The code a patient enters at registration to join a clinic (PRD 09 S4.4, PRD 11 E3). */
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('join_code', 16)->nullable()->unique();
        });

        // every existing clinic gets a code
        foreach (DB::table('clinics')->whereNull('join_code')->pluck('id') as $id) {
            DB::table('clinics')->where('id', $id)->update(['join_code' => Clinic::newJoinCode()]);
        }
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropUnique(['join_code']);
            $table->dropColumn('join_code');
        });
    }
};

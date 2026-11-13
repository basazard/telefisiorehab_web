<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Who accepted which version of the consent statements, and when (PRD 11 E4). Append only. */
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('version', 32);
            $table->json('items');                      // the statement keys that were ticked
            $table->timestamp('accepted_at');

            $table->index(['user_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};

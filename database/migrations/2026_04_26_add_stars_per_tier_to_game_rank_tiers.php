<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_rank_tiers', function (Blueprint $table) {
            $table->integer('stars_per_tier')->default(1)->after('tier_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_rank_tiers', function (Blueprint $table) {
            $table->dropColumn('stars_per_tier');
        });
    }
};

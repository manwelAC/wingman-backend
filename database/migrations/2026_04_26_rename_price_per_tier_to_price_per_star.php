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
        Schema::table('pilot_pricing', function (Blueprint $table) {
            $table->renameColumn('price_per_tier', 'price_per_star');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_pricing', function (Blueprint $table) {
            $table->renameColumn('price_per_star', 'price_per_tier');
        });
    }
};

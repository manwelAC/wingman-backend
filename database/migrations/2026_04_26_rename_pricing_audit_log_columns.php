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
        Schema::table('pricing_audit_log', function (Blueprint $table) {
            $table->renameColumn('old_price_per_tier', 'old_price_per_star');
            $table->renameColumn('new_price_per_tier', 'new_price_per_star');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_audit_log', function (Blueprint $table) {
            $table->renameColumn('old_price_per_star', 'old_price_per_tier');
            $table->renameColumn('new_price_per_star', 'new_price_per_tier');
        });
    }
};

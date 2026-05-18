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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['grind_id']);
            
            // Re-add with cascade delete
            $table->foreign('grind_id')
                ->references('id')
                ->on('grinds')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Drop the cascade delete constraint
            $table->dropForeign(['grind_id']);
            
            // Re-add without cascade (nullable)
            $table->foreign('grind_id')
                ->references('id')
                ->on('grinds')
                ->nullOnDelete();
        });
    }
};

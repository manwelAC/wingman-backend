<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilot_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pilot_id')->constrained('users')->onDelete('cascade');
            $table->enum('game', ['CODM', 'MLBB', 'Valorant']);
            $table->string('range_name');
            $table->foreignId('tier_start_id')->constrained('game_rank_tiers');
            $table->foreignId('tier_end_id')->constrained('game_rank_tiers');
            $table->decimal('price_per_tier', 10, 2);
            $table->decimal('major_rank_crossing_fee', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['pilot_id', 'game', 'tier_start_id', 'tier_end_id']);
            $table->index(['pilot_id', 'game']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilot_pricing');
    }
};
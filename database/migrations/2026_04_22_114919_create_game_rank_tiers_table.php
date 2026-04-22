<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rank_tiers', function (Blueprint $table) {
            $table->id();
            $table->enum('game', ['CODM', 'MLBB', 'Valorant']);
            $table->string('tier_name');
            $table->integer('tier_order');
            $table->string('rank_group');
            $table->string('tier_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['game', 'tier_name']);
            $table->index(['game', 'tier_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_rank_tiers');
    }
};
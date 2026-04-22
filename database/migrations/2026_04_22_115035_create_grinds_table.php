<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grinds', function (Blueprint $table) {
            $table->id();
            $table->string('grind_number')->unique();
            $table->foreignId('pilot_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->enum('game', ['CODM', 'MLBB', 'Valorant']);
            $table->enum('service_type', ['rank_boost', 'win_count']);

            // Rank boost fields
            $table->foreignId('starting_tier_id')->nullable()->constrained('game_rank_tiers');
            $table->foreignId('target_tier_id')->nullable()->constrained('game_rank_tiers');
            $table->integer('total_tiers')->default(0);

            // Win count fields
            $table->integer('target_wins')->nullable();
            $table->decimal('price_per_win', 10, 2)->nullable();

            // Pricing
            $table->decimal('base_price', 10, 2);
            $table->decimal('final_price', 10, 2);

            // Status & Progress
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('progress_percentage')->default(0);
            $table->string('current_tier')->nullable();

            // Account & Notes
            $table->string('account_username')->nullable();
            $table->text('special_instructions')->nullable();

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['pilot_id', 'status']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grinds');
    }
};
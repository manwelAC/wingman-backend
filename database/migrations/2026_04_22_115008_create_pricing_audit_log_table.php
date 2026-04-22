<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pilot_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pricing_id')->constrained('pilot_pricing')->onDelete('cascade');
            $table->enum('action', ['created', 'updated', 'deactivated', 'reactivated']);
            $table->decimal('old_price_per_tier', 10, 2)->nullable();
            $table->decimal('new_price_per_tier', 10, 2)->nullable();
            $table->decimal('old_crossing_fee', 10, 2)->nullable();
            $table->decimal('new_crossing_fee', 10, 2)->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_audit_log');
    }
};
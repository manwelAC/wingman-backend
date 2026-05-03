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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->enum('type', ['earning', 'deduction', 'refund', 'fee', 'withdrawal']);
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('grind_id')->nullable();
            $table->unsignedBigInteger('payment_method_type_id')->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('balance_after', 12, 2);
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallets')
                ->onDelete('cascade');

            $table->foreign('grind_id')
                ->references('id')
                ->on('grinds')
                ->onDelete('set null');

            $table->foreign('payment_method_type_id')
                ->references('id')
                ->on('payment_method_types')
                ->onDelete('set null');

            $table->index(['wallet_id', 'created_at']);
            $table->index('type');
            $table->index('payment_method_type_id');
            $table->index('grind_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

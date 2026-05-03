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
        Schema::create('grind_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grind_id');
            $table->unsignedBigInteger('payment_method_type_id');
            $table->string('customer_reference', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('grind_id')->references('id')->on('grinds')->onDelete('cascade');
            $table->foreign('payment_method_type_id')->references('id')->on('payment_method_types');
            $table->unique('grind_id');

            $table->index('grind_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grind_payment_methods');
    }
};

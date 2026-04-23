<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grinds', function (Blueprint $table) {
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'cancelled'])
                ->default('not_started')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('grinds', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }
};
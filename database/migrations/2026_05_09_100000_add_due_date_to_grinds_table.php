<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grinds', function (Blueprint $table) {
            $table->dateTime('due_date')->nullable()->after('target_tier_id');
        });
    }

    public function down(): void
    {
        Schema::table('grinds', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};

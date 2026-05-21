<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: Drop old constraint and create new one
            DB::statement('ALTER TABLE grinds DROP CONSTRAINT IF EXISTS grinds_status_check');
            DB::statement("ALTER TABLE grinds ADD CONSTRAINT grinds_status_check CHECK (status IN ('not_started', 'in_progress', 'completed', 'cancelled'))");
            DB::statement("ALTER TABLE grinds ALTER COLUMN status SET DEFAULT 'not_started'");
        } else {
            // MySQL
            Schema::table('grinds', function (Blueprint $table) {
                $table->enum('status', ['not_started', 'in_progress', 'completed', 'cancelled'])
                    ->default('not_started')
                    ->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: Drop new constraint and create old one
            DB::statement('ALTER TABLE grinds DROP CONSTRAINT IF EXISTS grinds_status_check');
            DB::statement("ALTER TABLE grinds ADD CONSTRAINT grinds_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled'))");
            DB::statement("ALTER TABLE grinds ALTER COLUMN status SET DEFAULT 'pending'");
        } else {
            // MySQL
            Schema::table('grinds', function (Blueprint $table) {
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                    ->default('pending')
                    ->change();
            });
        }
    }
};
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
            DB::statement('ALTER TABLE pricing_audit_log DROP CONSTRAINT IF EXISTS pricing_audit_log_action_check');
            DB::statement("ALTER TABLE pricing_audit_log ADD CONSTRAINT pricing_audit_log_action_check CHECK (action IN ('created', 'updated', 'deactivated', 'reactivated', 'deleted'))");
        } else {
            // MySQL
            DB::statement("ALTER TABLE pricing_audit_log MODIFY action ENUM('created', 'updated', 'deactivated', 'reactivated', 'deleted')");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: Drop new constraint and create old one
            DB::statement('ALTER TABLE pricing_audit_log DROP CONSTRAINT IF EXISTS pricing_audit_log_action_check');
            DB::statement("ALTER TABLE pricing_audit_log ADD CONSTRAINT pricing_audit_log_action_check CHECK (action IN ('created', 'updated', 'deactivated', 'reactivated'))");
        } else {
            // MySQL
            DB::statement("ALTER TABLE pricing_audit_log MODIFY action ENUM('created', 'updated', 'deactivated', 'reactivated')");
        }
    }
};

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
        Schema::table('users', function (Blueprint $table) {
            $table->ipAddress('last_login_ip')->nullable()->after('fingerprint_enrolled');
            $table->string('last_login_city')->nullable()->after('last_login_ip');
            $table->string('last_login_country')->nullable()->after('last_login_city');
            $table->timestamp('last_login_at')->nullable()->after('last_login_country');
            $table->json('trusted_locations')->nullable()->after('last_login_at')->comment('Array of trusted city/country combinations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_ip',
                'last_login_city',
                'last_login_country',
                'last_login_at',
                'trusted_locations',
            ]);
        });
    }
};

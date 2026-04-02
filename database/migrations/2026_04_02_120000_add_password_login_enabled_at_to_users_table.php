<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_login_enabled_at')->nullable()->after('password');
        });

        DB::table('users')
            ->whereNull('google_id')
            ->whereNull('apple_id')
            ->whereNotNull('password')
            ->update([
                'password_login_enabled_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_login_enabled_at');
        });
    }
};

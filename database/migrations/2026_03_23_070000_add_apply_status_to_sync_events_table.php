<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_events', function (Blueprint $table) {
            $table->timestamp('applied_at')->nullable()->after('received_at');
            $table->text('apply_error')->nullable()->after('applied_at');
        });
    }

    public function down(): void
    {
        Schema::table('sync_events', function (Blueprint $table) {
            $table->dropColumn(['applied_at', 'apply_error']);
        });
    }
};

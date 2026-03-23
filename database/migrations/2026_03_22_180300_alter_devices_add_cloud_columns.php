<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->after('tenant_id')->constrained('stores')->nullOnDelete();
            $table->string('device_type', 40)->nullable()->after('platform');
            $table->string('app_mode', 40)->nullable()->after('device_type');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn(['device_type', 'app_mode']);
        });
    }
};

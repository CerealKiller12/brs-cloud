<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 120)->unique();
            $table->string('name', 120)->nullable();
            $table->string('platform', 40);
            $table->string('channel', 40)->default('stable');
            $table->string('branch_name', 120)->nullable();
            $table->string('current_version', 40)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->longText('metadata_json')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

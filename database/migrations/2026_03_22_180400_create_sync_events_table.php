<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('device_row_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('device_id', 120);
            $table->string('event_id', 120);
            $table->string('aggregate_type', 60);
            $table->string('event_type', 120);
            $table->timestamp('occurred_at');
            $table->json('payload_json');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'event_id']);
            $table->index(['store_id', 'device_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_events');
    }
};

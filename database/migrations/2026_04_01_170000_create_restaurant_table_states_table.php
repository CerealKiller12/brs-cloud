<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_table_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('table_id', 80);
            $table->string('table_label', 120)->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->json('cart_json')->nullable();
            $table->unsignedSmallInteger('guest_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('last_device_id', 120)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'table_id']);
            $table->index(['tenant_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_table_states');
    }
};

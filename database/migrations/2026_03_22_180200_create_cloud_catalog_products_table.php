<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloud_catalog_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('sku', 80);
            $table->string('barcode', 120)->nullable();
            $table->string('name', 160);
            $table->integer('price_cents');
            $table->integer('cost_cents')->default(0);
            $table->integer('stock_on_hand')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('catalog_version')->default(1);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'sku']);
            $table->unique(['store_id', 'barcode']);
            $table->index(['store_id', 'is_active', 'catalog_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud_catalog_products');
    }
};

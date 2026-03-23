<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloud_catalog_tombstones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('sku', 80)->nullable();
            $table->string('barcode', 120)->nullable();
            $table->unsignedBigInteger('catalog_version');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'catalog_version']);
            $table->index(['store_id', 'sku']);
            $table->index(['store_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud_catalog_tombstones');
    }
};

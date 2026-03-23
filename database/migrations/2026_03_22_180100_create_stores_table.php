<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 80)->unique();
            $table->string('timezone', 80)->default('America/Tijuana');
            $table->string('api_key', 120);
            $table->unsignedInteger('catalog_version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('branding_json')->nullable();
            $table->json('role_access_json')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};

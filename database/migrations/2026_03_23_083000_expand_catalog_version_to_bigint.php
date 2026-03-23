<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('alter table stores modify catalog_version bigint unsigned not null default 1');
        DB::statement('alter table cloud_catalog_products modify catalog_version bigint unsigned not null default 1');
    }

    public function down(): void
    {
        DB::statement('alter table stores modify catalog_version int unsigned not null default 1');
        DB::statement('alter table cloud_catalog_products modify catalog_version int unsigned not null default 1');
    }
};

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $now = now();

        DB::table('tenants')->updateOrInsert(
            ['slug' => 'baja-retail-system-demo'],
            [
                'name' => 'Venpi Demo',
                'plan_code' => 'growth',
                'subscription_status' => 'active',
                'is_active' => true,
                'trial_ends_at' => null,
                'onboarding_completed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $tenant = DB::table('tenants')->where('slug', 'baja-retail-system-demo')->first();

        $defaultRoleAccess = [
            'admin' => [
                'checkout' => true,
                'sales' => true,
                'cash' => true,
                'products' => true,
                'users' => true,
                'settings' => true,
                'updates' => true,
            ],
            'supervisor' => [
                'checkout' => true,
                'sales' => true,
                'cash' => true,
                'products' => true,
                'users' => false,
                'settings' => false,
                'updates' => false,
            ],
            'cashier' => [
                'checkout' => true,
                'sales' => true,
                'cash' => true,
                'products' => false,
                'users' => false,
                'settings' => false,
                'updates' => false,
            ],
        ];

        DB::table('stores')->updateOrInsert(
            ['code' => 'SUCURSAL-PRINCIPAL'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Sucursal principal',
                'timezone' => 'America/Tijuana',
                'api_key' => 'brs_demo_store_key_001',
                'catalog_version' => 3,
                'is_active' => true,
                'branding_json' => json_encode([
                    'business_name' => 'Venpi',
                    'terminal_name' => 'Caja principal',
                ]),
                'role_access_json' => json_encode($defaultRoleAccess),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $store = DB::table('stores')->where('code', 'SUCURSAL-PRINCIPAL')->first();

        $catalogProducts = [
            ['sku' => 'SKU-0001', 'barcode' => '7501002010000', 'name' => 'Agua mineral 600ml', 'price_cents' => 1800, 'cost_cents' => 700, 'stock_on_hand' => 32, 'reorder_point' => 6],
            ['sku' => 'SKU-0002', 'barcode' => '7501002010001', 'name' => 'Chocolate premium', 'price_cents' => 4500, 'cost_cents' => 2100, 'stock_on_hand' => 14, 'reorder_point' => 5],
            ['sku' => 'SKU-0003', 'barcode' => '7501002010002', 'name' => 'Cafe molido 250g', 'price_cents' => 8900, 'cost_cents' => 5400, 'stock_on_hand' => 9, 'reorder_point' => 4],
            ['sku' => 'SKU-0004', 'barcode' => '7501002010003', 'name' => 'Botana artesanal 90g', 'price_cents' => 3200, 'cost_cents' => 1400, 'stock_on_hand' => 18, 'reorder_point' => 5],
            ['sku' => 'SKU-0005', 'barcode' => '7501002010004', 'name' => 'Leche deslactosada 1L', 'price_cents' => 3950, 'cost_cents' => 2700, 'stock_on_hand' => 11, 'reorder_point' => 5],
        ];

        foreach ($catalogProducts as $product) {
            DB::table('cloud_catalog_products')->updateOrInsert(
                [
                    'store_id' => $store->id,
                    'sku' => $product['sku'],
                ],
                [
                    'barcode' => $product['barcode'],
                    'name' => $product['name'],
                    'price_cents' => $product['price_cents'],
                    'cost_cents' => $product['cost_cents'],
                    'stock_on_hand' => $product['stock_on_hand'],
                    'reorder_point' => $product['reorder_point'],
                    'track_inventory' => true,
                    'is_active' => true,
                    'catalog_version' => 3,
                    'metadata_json' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        User::updateOrCreate(
            ['email' => 'owner@bajaretailsystem.demo'],
            [
                'tenant_id' => $tenant->id,
                'store_id' => $store->id,
                'name' => 'Luis Garcia',
                'role' => 'owner',
                'is_active' => true,
                'email_verified_at' => $now,
                'password' => 'demo1234',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}

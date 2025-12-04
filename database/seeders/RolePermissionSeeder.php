<?php

namespace Database\Seeders;

use App\Enum\PermissionEnum;
use App\Enum\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::create(['name' => $permission->value]);
        }
        $superAdmin = Role::firstOrCreate(['name' => RolesEnum::SUPER_ADMIN->value]);
        $superAdmin->givePermissionTo(Permission::all());

        $merchantOwner = Role::firstOrCreate(['name' => RolesEnum::MERCHANT_OWNER->value]);
        $merchantOwner->givePermissionTo([
            PermissionEnum::MANAGE_OWN_MERCHANT->value,      // Kelola setting toko
            PermissionEnum::MANAGE_MERCHANT_PRODUCTS->value, // Kelola produk
            PermissionEnum::VIEW_MERCHANT_REPORTS->value,    // Lihat laporan
            PermissionEnum::MANAGE_WAREHOUSES->value,        // Kelola gudang
            PermissionEnum::MANAGE_WAREHOUSE_STOCK->value,   // Kelola stok
            PermissionEnum::CREATE_TRANSACTION->value,       // Bisa transaksi juga
            PermissionEnum::VOID_TRANSACTION->value,         // Void transaksi
        ]);

        $adminMerchant = Role::firstOrCreate(['name' => RolesEnum::ADMIN->value]);
        $adminMerchant->givePermissionTo([
            PermissionEnum::MANAGE_MERCHANT_PRODUCTS->value, // Input produk
            PermissionEnum::VIEW_MERCHANT_REPORTS->value,    // Rekap laporan
            PermissionEnum::MANAGE_WAREHOUSE_STOCK->value,   // Cek stok
        ]);

        $cashier = Role::firstOrCreate(['name' => RolesEnum::CASHIER->value]);
        $cashier->givePermissionTo([
            PermissionEnum::CREATE_TRANSACTION->value,       // Kasir
            PermissionEnum::VIEW_CATALOG->value,             // Lihat barang
            PermissionEnum::VIEW_PRODUCT_DETAIL->value,
        ]);

        $warehouseStaff = Role::firstOrCreate(['name' => RolesEnum::WAREHOUSE_STAFF->value]);
        $warehouseStaff->givePermissionTo([
            PermissionEnum::MANAGE_WAREHOUSES->value,        // Kelola data gudang
            PermissionEnum::MANAGE_WAREHOUSE_STOCK->value,   // Opname stok
        ]);

        $guest = Role::firstOrCreate(['name' => RolesEnum::GUEST_USER->value]);
        $guest->givePermissionTo([
            PermissionEnum::VIEW_CATALOG->value,
            PermissionEnum::VIEW_PRODUCT_DETAIL->value,
            PermissionEnum::PLACE_ORDER->value,
            PermissionEnum::MANAGE_MY_PROFILE->value
        ]);
    }
}

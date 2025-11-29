<?php

namespace Database\Seeders;

use App\Enum\PermissionEnum;
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
        $superAdmin = Role::create(['name' => 'super_admin']);

        $superAdmin->givePermissionTo(Permission::all());

        $merchantOwner = Role::firstOrCreate(['name' => 'merchant_owner']);
        $merchantOwner->givePermissionTo([
            PermissionEnum::MANAGE_OWN_MERCHANT->value,
            PermissionEnum::MANAGE_MERCHANT_PRODUCTS->value,
            PermissionEnum::VIEW_MERCHANT_REPORTS->value,
            PermissionEnum::CREATE_TRANSACTION->value,
            PermissionEnum::VOID_TRANSACTION->value,
            PermissionEnum::MANAGE_WAREHOUSE_STOCK->value,
        ]);
        $staff = Role::firstOrCreate(['name' => 'warehouse_staff']);
        $staff->givePermissionTo([
            PermissionEnum::MANAGE_WAREHOUSES->value,
            PermissionEnum::MANAGE_WAREHOUSE_STOCK->value,
        ]);

        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->givePermissionTo([
            PermissionEnum::CREATE_TRANSACTION->value,
            PermissionEnum::VIEW_CATALOG->value,
            PermissionEnum::VIEW_PRODUCT_DETAIL->value,
        ]);

        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->givePermissionTo([
            PermissionEnum::VIEW_CATALOG->value,
            PermissionEnum::VIEW_PRODUCT_DETAIL->value,
            PermissionEnum::PLACE_ORDER->value,
            PermissionEnum::VIEW_MY_ORDERS->value,
            PermissionEnum::MANAGE_MY_PROFILE->value,
        ]);

        $guest = Role::firstOrCreate(['name' => 'guest']);
        $guest->givePermissionTo([
            PermissionEnum::VIEW_CATALOG->value,
            PermissionEnum::VIEW_PRODUCT_DETAIL->value,
        ]);
    }
}

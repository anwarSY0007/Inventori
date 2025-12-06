<?php

namespace App\Enum;

enum RolesEnum: string
{
    case SUPER_ADMIN = 'super_admin';
    case MERCHANT_OWNER = 'merchant_owner';
    case ADMIN = 'admin_merchant';
    case CASHIER = 'cashier';
    case WAREHOUSE_STAFF = 'warehouse_staff';
    case CUSTOMER = 'customer';
    case GUEST = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::MERCHANT_OWNER => 'Pemilik Toko (Merchant)',
            self::ADMIN => 'admin Toko(Merchant)',
            self::CASHIER => 'Kasir',
            self::WAREHOUSE_STAFF => 'Staf Gudang',
            self::CUSTOMER => 'Pelanggan Terdaftar',
            self::GUEST => 'Pengunjung (Tamu)',
        };
    }

    /**
     * Method ini yang dipanggil oleh Seeder untuk mendapatkan daftar permission
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => PermissionEnum::cases(), // Punya semua permission

            self::MERCHANT_OWNER => [
                PermissionEnum::MANAGE_OWN_MERCHANT,
                PermissionEnum::MANAGE_OWN_TEAM,        // Permission baru
                PermissionEnum::MANAGE_MERCHANT_PRODUCTS,
                PermissionEnum::VIEW_MERCHANT_REPORTS,
                PermissionEnum::MANAGE_WAREHOUSES,
                PermissionEnum::MANAGE_WAREHOUSE_STOCK,
                PermissionEnum::CREATE_TRANSACTION,
                PermissionEnum::VOID_TRANSACTION,
            ],

            self::ADMIN => [
                PermissionEnum::MANAGE_MERCHANT_PRODUCTS,
                PermissionEnum::VIEW_MERCHANT_REPORTS,
                PermissionEnum::MANAGE_WAREHOUSE_STOCK,
            ],

            self::CASHIER => [
                PermissionEnum::CREATE_TRANSACTION,
                PermissionEnum::VIEW_CATALOG,
                PermissionEnum::VIEW_PRODUCT_DETAIL,
            ],

            self::WAREHOUSE_STAFF => [
                PermissionEnum::MANAGE_WAREHOUSES,
                PermissionEnum::MANAGE_WAREHOUSE_STOCK,
            ],

            self::CUSTOMER => [
                PermissionEnum::VIEW_CATALOG,
                PermissionEnum::VIEW_PRODUCT_DETAIL,
                PermissionEnum::PLACE_ORDER,
                PermissionEnum::VIEW_MY_ORDERS,
                PermissionEnum::MANAGE_MY_PROFILE,
            ],

            self::GUEST => [
                PermissionEnum::VIEW_CATALOG,
                PermissionEnum::VIEW_PRODUCT_DETAIL,
            ],
        };
    }

    /**
     * Get all roles that should see admin sidebar
     */
    public static function adminRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::MERCHANT_OWNER->value,
            self::ADMIN->value,
            self::CASHIER->value,
            self::WAREHOUSE_STAFF->value,
        ];
    }

    /**
     * Get all roles that can manage team
     */
    public static function teamManagementRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::MERCHANT_OWNER->value,
            self::ADMIN->value,
        ];
    }

    /**
     * Get all roles that can view customers
     */
    public static function customerViewRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::MERCHANT_OWNER->value,
            self::ADMIN->value,
            self::CASHIER->value,
            self::WAREHOUSE_STAFF->value,
        ];
    }
}

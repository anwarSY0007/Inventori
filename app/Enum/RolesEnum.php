<?php

namespace App\Enum;

enum RolesEnum: string
{
    case SUPER_ADMIN = 'super_admin';
    case MERCHANT_OWNER = 'merchant_owner';
    case ADMIN = 'admin_merchant';
    case CASHIER = 'cashier';
    case WAREHOUSE_STAFF = 'warehouse_staff';
    case GUEST_USER = 'customers';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::MERCHANT_OWNER => 'Pemilik Toko (Merchant)',
            self::ADMIN => 'admin Toko(Merchant)',
            self::CASHIER => 'Kasir',
            self::WAREHOUSE_STAFF => 'Staf Gudang',
            self::GUEST_USER => 'guest',
        };
    }
}

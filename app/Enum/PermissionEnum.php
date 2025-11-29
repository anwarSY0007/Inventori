<?php

namespace App\Enum;

enum PermissionEnum: string
{
    case MANAGE_CATEGORIES = 'manage categories';
    case MANAGE_GLOBAL_PRODUCTS = 'manage global products';

    case MANAGE_WAREHOUSES = 'manage warehouses';
    case MANAGE_WAREHOUSE_STOCK = 'manage warehouse stock';

    case MANAGE_MERCHANTS = 'manage merchants'; // Super admin
    case MANAGE_OWN_MERCHANT = 'manage own merchant'; // Merchant owner
    case MANAGE_MERCHANT_PRODUCTS = 'manage merchant products';
    case VIEW_MERCHANT_REPORTS = 'view merchant reports';

    case CREATE_TRANSACTION = 'create transaction'; // Kasir/Admin
    case VOID_TRANSACTION = 'void transaction';

    case VIEW_CATALOG = 'view catalog'; // Bisa lihat list produk
    case VIEW_PRODUCT_DETAIL = 'view product detail'; // Bisa lihat detail

    case PLACE_ORDER = 'place order'; // Checkout sendiri (online)
    case VIEW_MY_ORDERS = 'view my orders'; // Lihat riwayat belanja sendiri
    case MANAGE_MY_PROFILE = 'manage my profile'; // Edit profil sendiri

    public function label(): string
    {
        return match ($this) {
            self::MANAGE_CATEGORIES => 'Kelola Kategori',
            self::MANAGE_GLOBAL_PRODUCTS => 'Kelola Produk Global',
            self::MANAGE_WAREHOUSES => 'Kelola Gudang',
            self::MANAGE_WAREHOUSE_STOCK => 'Kelola Stok Gudang',
            self::MANAGE_MERCHANTS => 'Kelola Semua Merchant',
            self::MANAGE_OWN_MERCHANT => 'Kelola Merchant Sendiri',
            self::MANAGE_MERCHANT_PRODUCTS => 'Kelola Produk Merchant',
            self::VIEW_MERCHANT_REPORTS => 'Lihat Laporan Merchant',
            self::CREATE_TRANSACTION => 'Buat Transaksi (Kasir)',
            self::VOID_TRANSACTION => 'Batalkan Transaksi',

            // Label untuk Guest & Customer
            self::VIEW_CATALOG => 'Lihat Katalog Produk',
            self::VIEW_PRODUCT_DETAIL => 'Lihat Detail Produk',
            self::PLACE_ORDER => 'Melakukan Pemesanan (Online)',
            self::VIEW_MY_ORDERS => 'Lihat Pesanan Saya',
            self::MANAGE_MY_PROFILE => 'Kelola Profil Saya',
        };
    }
}

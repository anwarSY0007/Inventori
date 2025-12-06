<?php

namespace Database\Seeders;

use App\Enum\PermissionEnum;
use App\Enum\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset cache permission Spatie agar perubahan terdeteksi
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. Buat semua Permission yang terdaftar di Enum
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value]);
        }

        // 3. Buat Role & Assign Permission secara otomatis dari RolesEnum
        foreach (RolesEnum::cases() as $roleEnum) {
            // Buat atau ambil Role berdasarkan value Enum (contoh: 'merchant_owner')
            $role = Role::firstOrCreate(['name' => $roleEnum->value]);

            // Ambil daftar permission dari method permissions() di RolesEnum
            $assignedPermissions = $roleEnum->permissions();

            // Ubah array Enum menjadi array string value
            // Contoh: [PermissionEnum::MANAGE_OWN_TEAM] -> ['manage own team']
            $permissionValues = array_map(
                fn($p) => $p->value,
                $assignedPermissions
            );

            // Sinkronisasi permission ke role (hapus yang lama, pasang yang baru sesuai Enum)
            $role->syncPermissions($permissionValues);
        }
    }
}

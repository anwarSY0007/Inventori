<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\UserService;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $userService = app(UserService::class);
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@app.com',
            'phone' => '08111111111',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super_admin');
        $this->command->info('✅ Super Admin created: admin@app.com');

        // ---------------------------------------------------
        // B. MERCHANT OWNER (Kepala Gudang - Punya Toko)
        // ---------------------------------------------------
        // Kita gunakan function createMerchantWithTeam() yang baru saja kita buat
        $merchant = $userService->registerMerchantWithTeam([
            'name' => 'Juragan Toko',
            'email' => 'owner@app.com',
            'phone' => '08222222222',
            'password' => 'password',
            'store_name' => 'Toko Maju Jaya', // Nama tim/toko
            'role' => 'merchant_owner' // Sesuai RolePermissionSeeder
        ]);
        // Manual verify email karena lewat seeder
        $merchant->email_verified_at = now();
        $merchant->saveQuietly();

        $this->command->info('✅ Merchant Owner created: owner@app.com (Toko: Toko Maju Jaya)');

        // ---------------------------------------------------
        // C. CUSTOMER (User Biasa - Tidak punya Toko)
        // ---------------------------------------------------
        $customer = $userService->register([
            'name' => 'Budi Customer',
            'email' => 'customer@app.com',
            'phone' => '08333333333',
            'password' => 'password',
            'role' => 'customer'
        ]);
        $customer->email_verified_at = now();
        $customer->saveQuietly();

        $this->command->info('✅ Customer created: customer@app.com');
    }
}

<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
use App\Services\UserService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
            MerchantSeeder::class,
            CompleteAppSeeder::class
            // MerchantStaffSeeder::class
        ]);
        $userService = app(UserService::class);

        // ---------------------------------------------------
        // C. CUSTOMER (User Biasa - Tidak punya Toko)
        // ---------------------------------------------------
        // $customer = $userService->register([
        //     'name' => 'Budi Customer',
        //     'email' => 'customer@app.com',
        //     'phone' => '08333333333',
        //     'password' => 'password',
        //     'role' => RolesEnum::CUSTOMER->value
        // ]);
        // $customer->email_verified_at = now();
        // $customer->saveQuietly();
        // $this->command->info('✅ Customer created: customer@app.com');
    }
}

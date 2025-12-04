<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
{
    public function run(UserService $userService): void
    {
        if (User::where('email', 'kepalatoko@app.com')->exists()) {
            return;
        }

        $merchant = $userService->registerMerchantWithTeam([
            'name' => 'Budi Kepala Toko',
            'email' => 'kepalatoko@app.com',
            'phone' => '08222222222',
            'password' => 'password',
            'store_name' => 'Toko Cabang Jakarta', // Ini nama Tokonya
            'role' => RolesEnum::MERCHANT_OWNER->value
        ]);

        $merchant->email_verified_at = now();
        $merchant->saveQuietly();

        $this->command->info('✅ Kepala Toko: kepalatoko@app.com | Team: Toko Cabang Jakarta');
    }
}

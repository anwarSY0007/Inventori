<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use App\Services\UserService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MerchantStaffSeeder extends Seeder
{
    public function run(UserService $userService, TeamService $teamService): void
    {
        $tokoTeam = Team::where('slug', 'toko-cabang-jakarta')->first(); // Slug dari MerchantSeeder

        if (!$tokoTeam) {
            $this->command->error('❌ Toko tidak ditemukan. Jalankan MerchantSeeder dulu.');
            return;
        }

        $staffs = [
            [
                'role' => RolesEnum::ADMIN->value,
                'name' => 'Ani Admin',
                'email' => 'admin.toko@app.com',
                'phone' => '08555555551',
            ],
            [
                'role' => RolesEnum::CASHIER->value,
                'name' => 'Siti Kasir',
                'email' => 'kasir.toko@app.com',
                'phone' => '08555555552',
            ],
            [
                'role' => RolesEnum::WAREHOUSE_STAFF->value,
                'name' => 'Ujang Gudang',
                'email' => 'gudang.toko@app.com',
                'phone' => '08555555553',
            ],
        ];

        foreach ($staffs as $staffData) {
            // A. Buat User
            $user = User::firstOrCreate(
                ['email' => $staffData['email']],
                [
                    'name' => $staffData['name'],
                    'phone' => $staffData['phone'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // B. Assign Role (Spatie)
            $user->syncRoles($staffData['role']);
            if (!$tokoTeam->users()->where('user_id', $user->id)->exists()) {
                $tokoTeam->users()->attach($user->id, ['created_at' => now(), 'updated_at' => now()]);
            }
            $user->current_team_id = $tokoTeam->id;
            $user->saveQuietly();

            $this->command->info("   👤 Staff Created: {$staffData['name']} ({$staffData['role']}) -> Assigned to {$tokoTeam->name}");
        }
    }
}

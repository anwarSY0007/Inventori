<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
use App\Models\Merchant;
use App\Models\Team;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MerchantSeeder extends Seeder
{
    public function run(UserService $userService): void
    {
        // Data Dummy untuk 3 Toko Berbeda
        $merchantsData = [
            [
                'city' => 'Jakarta',
                'store_name' => 'Toko Cabang Jakarta',
                'store_phone' => '021-5551234',
                'store_address' => 'Jl. Sudirman Kav 52-53, Jakarta Selatan 12190',
                'owner' => ['name' => 'Budi Owner JKT', 'email' => 'owner.jkt@app.com', 'phone' => '08113232111'],
                'staffs' => [
                    ['role' => RolesEnum::ADMIN->value, 'name' => 'Ani Admin JKT', 'email' => 'admin.jkt@app.com', 'phone' => '08122222221'],
                    ['role' => RolesEnum::CASHIER->value, 'name' => 'Siti Kasir JKT', 'email' => 'kasir.jkt@app.com', 'phone' => '08122222222'],
                    ['role' => RolesEnum::WAREHOUSE_STAFF->value, 'name' => 'Ujang Gudang JKT', 'email' => 'gudang.jkt@app.com', 'phone' => '08122222223'],
                ],
                'customers' => [
                    ['name' => 'Pelanggan JKT A', 'email' => 'cust.jkt.a@app.com', 'phone' => '08133333331'],
                    ['name' => 'Pelanggan JKT B', 'email' => 'cust.jkt.b@app.com', 'phone' => '08133333332'],
                ]
            ],
            [
                'city' => 'Bandung',
                'store_name' => 'Toko Cabang Bandung',
                'store_phone' => '022-2501234',
                'store_address' => 'Jl. Dago No 45, Bandung 40135',
                'owner' => ['name' => 'Asep Owner BDG', 'email' => 'owner.bdg@app.com', 'phone' => '08212221111'],
                'staffs' => [
                    ['role' => RolesEnum::ADMIN->value, 'name' => 'Euis Admin BDG', 'email' => 'admin.bdg@app.com', 'phone' => '08222222221'],
                    ['role' => RolesEnum::CASHIER->value, 'name' => 'Neng Kasir BDG', 'email' => 'kasir.bdg@app.com', 'phone' => '08222222222'],
                ],
                'customers' => [
                    ['name' => 'Pelanggan BDG A', 'email' => 'cust.bdg.a@app.com', 'phone' => '08233333331'],
                ]
            ],
            [
                'city' => 'Surabaya',
                'store_name' => 'Toko Cabang Surabaya',
                'store_phone' => '031-5321234',
                'store_address' => 'Jl. Tunjungan No 88, Surabaya 60275',
                'owner' => ['name' => 'Joko Owner SBY', 'email' => 'owner.sby@app.com', 'phone' => '08311111111'],
                'staffs' => [
                    ['role' => RolesEnum::ADMIN->value, 'name' => 'Rudi Admin SBY', 'email' => 'admin.sby@app.com', 'phone' => '08322222220'],
                    ['role' => RolesEnum::CASHIER->value, 'name' => 'Dewi Kasir SBY', 'email' => 'kasir.sby@app.com', 'phone' => '08322222221'],
                    ['role' => RolesEnum::WAREHOUSE_STAFF->value, 'name' => 'Bambang Gudang SBY', 'email' => 'gudang.sby@app.com', 'phone' => '08322222222'],
                ],
                'customers' => [
                    ['name' => 'Pelanggan SBY A', 'email' => 'cust.sby.a@app.com', 'phone' => '08333333331'],
                ]
            ],
        ];

        foreach ($merchantsData as $data) {
            $this->command->info("🏗️  Memproses: {$data['store_name']}...");

            // 1. Check if owner already exists
            if (User::where('email', $data['owner']['email'])->exists()) {
                $this->command->warn("   ⚠️ Owner {$data['owner']['email']} sudah ada. Skip.");
                continue;
            }

            // 2. Register Merchant Owner & Create Team
            $owner = $userService->registerMerchantWithTeam([
                'name' => $data['owner']['name'],
                'email' => $data['owner']['email'],
                'phone' => $data['owner']['phone'],
                'password' => 'password',
                'store_name' => $data['store_name'],
                'role' => RolesEnum::MERCHANT_OWNER->value
            ]);

            // Ensure role is assigned
            $owner->assignRole(RolesEnum::MERCHANT_OWNER->value);
            $owner->email_verified_at = now();
            $owner->saveQuietly();

            // Get the team created by UserService
            $team = Team::where('keeper_id', $owner->id)->first();

            if (!$team) {
                $this->command->error("   ❌ Team not found for owner: {$owner->email}");
                continue;
            }

            // 3. CREATE MERCHANT RECORD (THIS WAS MISSING!)
            $merchant = Merchant::create([
                'name' => $data['store_name'],
                'phone' => $data['store_phone'],
                'alamat' => $data['store_address'],
                'description' => "Main branch in {$data['city']}",
                'keeper_id' => $owner->id,
                'thumbnail' => null,
            ]);

            $this->command->info("   ✓ Merchant created: {$merchant->name} (ID: {$merchant->id})");

            // 4. Create Staffs & Assign to Team
            foreach ($data['staffs'] as $staffData) {
                $staff = User::firstOrCreate(
                    ['email' => $staffData['email']],
                    [
                        'name' => $staffData['name'],
                        'phone' => $staffData['phone'],
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]
                );

                // Assign Role
                $staff->syncRoles($staffData['role']);

                // Attach to Team
                if (!$team->users()->where('user_id', $staff->id)->exists()) {
                    $team->users()->attach($staff->id, ['created_at' => now(), 'updated_at' => now()]);
                }

                // Set Current Team
                $staff->current_team_id = $team->id;
                $staff->saveQuietly();

                $this->command->info("   ✓ Staff: {$staff->name} ({$staffData['role']})");
            }

            // 5. Create Customers
            foreach ($data['customers'] as $custData) {
                $customer = $userService->register([
                    'name' => $custData['name'],
                    'email' => $custData['email'],
                    'phone' => $custData['phone'],
                    'password' => 'password',
                    'role' => RolesEnum::CUSTOMER->value
                ]);

                $customer->assignRole(RolesEnum::CUSTOMER->value);
                $customer->email_verified_at = now();
                $customer->saveQuietly();

                // Attach customer to team
                if (!$team->customers()->where('user_id', $customer->id)->exists()) {
                    $team->customers()->attach($customer->id, ['created_at' => now(), 'updated_at' => now()]);
                }

                $this->command->info("   ✓ Customer: {$customer->name}");
            }

            $this->command->info("   ✅ Selesai: {$data['store_name']} (Staff: " . count($data['staffs']) . ", Cust: " . count($data['customers']) . ")");
            $this->command->info('');
        }
    }
}

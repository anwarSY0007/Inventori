<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
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
                'owner' => ['name' => 'Budi Owner JKT', 'email' => 'owner.jkt@app.com', 'phone' => '08113232111'],
                'staffs' => [
                    // Kepala Toko / Admin Toko
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
                'owner' => ['name' => 'Asep Owner BDG', 'email' => 'owner.bdg@app.com', 'phone' => '08212221111'],
                'staffs' => [
                    // Kepala Toko / Admin Toko
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
                'owner' => ['name' => 'Joko Owner SBY', 'email' => 'owner.sby@app.com', 'phone' => '08311111111'],
                'staffs' => [
                    // Note: Di data lama Surabaya tidak punya ADMIN (Kepala Toko), saya tambahkan agar lengkap
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

            // 1. Register Merchant Owner & Create Team
            if (User::where('email', $data['owner']['email'])->exists()) {
                $this->command->warn("   ⚠️ Owner {$data['owner']['email']} sudah ada. Skip.");
                continue;
            }

            $owner = $userService->registerMerchantWithTeam([
                'name' => $data['owner']['name'],
                'email' => $data['owner']['email'],
                'phone' => $data['owner']['phone'],
                'password' => 'password',
                'store_name' => $data['store_name'],
                'role' => RolesEnum::MERCHANT_OWNER->value
            ]);

            // --- FIX START ---
            // Pastikan Role ter-assign secara eksplisit ke model User Owner
            // Ini untuk jaga-jaga jika logic di UserService tidak melakukan assignRole
            $owner->assignRole(RolesEnum::MERCHANT_OWNER->value);
            // --- FIX END ---

            $owner->email_verified_at = now();
            $owner->saveQuietly();

            // Ambil Team yang baru saja dibuat oleh service
            $team = Team::where('keeper_id', $owner->id)->first();

            // 2. Create Staffs & Assign to Team
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

                // Assign Role (Admin/Kasir/Gudang)
                // Disini 'Admin' bertindak sebagai 'Kepala Toko' sesuai RolesEnum
                $staff->syncRoles($staffData['role']);

                // Attach ke Team Toko tersebut
                if (!$team->users()->where('user_id', $staff->id)->exists()) {
                    $team->users()->attach($staff->id, ['created_at' => now(), 'updated_at' => now()]);
                }

                // Set Current Team ID agar saat login langsung masuk ke toko ini
                $staff->current_team_id = $team->id;
                $staff->saveQuietly();
            }

            // 3. Create Customers
            foreach ($data['customers'] as $custData) {
                $customer = $userService->register([
                    'name' => $custData['name'],
                    'email' => $custData['email'],
                    'phone' => $custData['phone'],
                    'password' => 'password',
                    'role' => RolesEnum::CUSTOMER->value
                ]);

                // Pastikan role customer juga ter-assign
                $customer->assignRole(RolesEnum::CUSTOMER->value);

                $customer->email_verified_at = now();
                $customer->saveQuietly();

                // Attach customer ke team toko (many-to-many relasi customer-merchant)
                // Asumsi ada tabel pivot atau logic attach customer ke toko
                if (!$team->customers()->where('user_id', $customer->id)->exists()) {
                    $team->customers()->attach($customer->id, ['created_at' => now(), 'updated_at' => now()]);
                }
            }

            $this->command->info("   ✅ Selesai: {$data['store_name']} (Staff: " . count($data['staffs']) . ", Cust: " . count($data['customers']) . ")");
        }
    }
}

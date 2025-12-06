<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'superadmin@app.com'],
            [
                'name' => 'Super Administrator',
                'phone' => '08111111111',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole(RolesEnum::SUPER_ADMIN->value);

        $teamName = strtok($admin->name, " ") . "'s Team";

        $adminTeam = Team::firstOrCreate(
            ['keeper_id' => $admin->id], // Cek by keeper_id agar tidak duplikat
            [
                'name' => $teamName,
                'slug' => Str::slug($teamName),
                'keeper_id' => $admin->id,
            ]
        );

        if (!$adminTeam->users()->where('user_id', $admin->id)->exists()) {
            $adminTeam->users()->attach($admin->id, ['created_at' => now(), 'updated_at' => now()]);
        }

        $admin->current_team_id = $adminTeam->id;
        $admin->saveQuietly();

        $this->command->info("✅ Super Admin created. Team: {$adminTeam->name}");
    }
}

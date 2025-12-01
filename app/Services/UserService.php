<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\TeamRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected TeamRepository $teamRepository
    ) {}

    /**
     * 
     * Register new user with automatic team creation
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
            ]);
            $this->userRepository->assignRole($user, 'customer');

            // [Audit Log]
            Log::info("New User Registered: {$user->email}", ['user_id' => $user->id, 'role' => 'customer']);

            return $user;
        });
    }

    public function registerMerchantWithTeam(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. Create User (tanpa trigger observer yang create team)
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
            ]);
            $role = $data['role'] ?? 'merchant_owner';
            $this->userRepository->assignRole($user, $role);

            // Create Team/Store
            $teamName = $data['store_name'] ?? $this->generateTeamName($user->name);

            $team = $this->teamRepository->createTeam([
                'name' => $teamName,
                'keeper_id' => $user->id,
            ]);

            $this->teamRepository->addMember($team, $user->id);
            $this->userRepository->setCurrentTeam($user, $team->id);

            // [Audit Log]
            Log::info("Merchant User Created: {$user->email}", [
                'user_id' => $user->id,
                'team_id' => $team->id,
                'created_by' => Auth::id()
            ]);

            return $user;
        });
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $updateData['email'] = $data['email'];
            }

            if (isset($data['phone'])) {
                $updateData['phone'] = $data['phone'];
            }

            if (isset($data['avatar'])) {
                // Handle avatar upload logic
                $updateData['avatar'] = $data['avatar'];
            }

            Log::info("User Profile Updated: {$user->id}");

            return $this->userRepository->update($user, $updateData);
        });
    }

    /**
     * Switch user's current team
     */
    public function switchTeam(User $user, string $teamId): User
    {
        // Validasi: pastikan user adalah member dari team tersebut
        if (!$user->teams()->where('teams.id', $teamId)->exists()) {
            throw new \Exception('User is not a member of this team');
        }
        $updatedUser = $this->userRepository->setCurrentTeam($user, $teamId);

        // [Audit Log]
        Log::info("User Switched Team", [
            'user_id' => $user->id,
            'new_team_id' => $teamId
        ]);

        return $updatedUser;
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, string $role): void
    {
        if (!Role::where('name', $role)->exists()) {
            throw new Exception("Role '{$role}' tidak ditemukan.");
        }

        $this->userRepository->assignRole($user, $role);

        // [Audit Log]
        Log::info("Role Assigned to User", [
            'target_user_id' => $user->id,
            'role' => $role,
            'assigned_by' => Auth::id()
        ]);
    }

    /**
     * Generate team name from user's first name
     */
    private function generateTeamName(string $fullName): string
    {
        $firstName = Str::before($fullName, ' ');
        return "{$firstName}'s team";
    }

    /**
     * Get user by ID with relations
     */
    public function getUserById(string $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Get all users with filters
     */
    public function getAllUsers(array $filters = [])
    {
        return $this->userRepository->getAll($filters);
    }
}

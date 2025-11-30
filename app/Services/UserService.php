<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\TeamRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected TeamRepository $teamRepository
    ) {}

    /**
     * Register new user with automatic team creation
     * 
     * Ini adalah SATU-SATUNYA tempat untuk handle user registration
     * Observer sudah di-disable untuk menghindari loop
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

            // 2. Create Personal Team
            $teamName = $this->generateTeamName($user->name);
            $team = $this->teamRepository->createTeam([
                'name' => $teamName,
                'keeper_id' => $user->id,
            ]);

            // 3. Attach user to team (many-to-many)
            $this->userRepository->attachToTeam($user, $team->id);

            // 4. Set as current team
            $this->userRepository->setCurrentTeam($user, $team->id);

            // 5. Assign default role (opsional)
            if (isset($data['role'])) {
                $this->userRepository->assignRole($user, $data['role']);
            }

            return $user->fresh(['teams', 'currentTeams']);
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

        return $this->userRepository->setCurrentTeam($user, $teamId);
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, string $role): void
    {
        $this->userRepository->assignRole($user, $role);
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

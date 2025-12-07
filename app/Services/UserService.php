<?php

namespace App\Services;

use App\Enum\RolesEnum;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\TeamRepository;
use Exception;
use Illuminate\Support\Collection;
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
     * Register new customer user
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            $role = $data['role'] ?? RolesEnum::CUSTOMER->value;

            $this->userRepository->assignRole($user, $role);

            // [Audit Log]
            Log::info("New User Registered: {$user->email}", ['user_id' => $user->id, 'role' => $role]);

            return $user;
        });
    }

    /**
     * Register new merchant with team
     */
    public function registerMerchantWithTeam(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create User
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            $role = $data['role'] ?? RolesEnum::MERCHANT_OWNER->value;
            $this->userRepository->assignRole($user, $role);

            // Create Team/Store
            $teamName = $data['store_name'] ?? $this->generateTeamName($user->name);

            $team = $this->teamRepository->createTeam([
                'name' => $teamName,
                'keeper_id' => $user->id,
            ]);

            $this->teamRepository->addMember($team, $user->id);
            $this->userRepository->setCurrentTeam($user, $team->id);

            Log::info("Merchant User Created: {$user->email}", [
                'user_id' => $user->id,
                'team_id' => $team->id,
                'role' => $role,
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
                $updateData['avatar'] = $data['avatar'];
            }

            Log::info("User Profile Updated", [
                'user_id' => $user->id,
                'updated_by' => Auth::id()
            ]);

            return $this->userRepository->update($user, $updateData);
        });
    }

    /**
     * Switch user's current team
     */
    public function switchTeam(User $user, string $teamId): User
    {
        // Validate: ensure user is member of this team
        if (!$user->teams()->where('teams.id', $teamId)->exists()) {
            throw new Exception('User is not a member of this team');
        }

        $updatedUser = $this->userRepository->setCurrentTeam($user, $teamId);

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
            throw new Exception("Role '{$role}' not found.");
        }

        $this->userRepository->assignRole($user, $role);

        Log::info("Role Assigned to User", [
            'target_user_id' => $user->id,
            'role' => $role,
            'assigned_by' => Auth::id()
        ]);
    }

    /**
     * Get user by ID with relations
     */
    public function getUserById(string $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Get all users (Super Admin only)
     * With optional filters: role, search, team_id
     */
    public function getAllUsers(array $filters = []): Collection
    {
        $query = User::with(['roles', 'teams', 'currentTeam']);

        // Filter by role
        if (isset($filters['role']) && !empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Filter by team
        if (isset($filters['team_id']) && !empty($filters['team_id'])) {
            $query->whereHas('teams', function ($q) use ($filters) {
                $q->where('teams.id', $filters['team_id']);
            });
        }

        // Search by name or email
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $this->userRepository->getAll($filters);
    }

    /**
     * Get all customers across all teams (Super Admin only)
     */
    public function getAllCustomers(array $filters = []): Collection
    {
        $query = User::with([
            'roles',
            'teams',
            'orders' => function ($q) {
                $q->select('customer_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(grand_total) as total'))
                    ->groupBy('customer_id');
            }
        ])->whereHas('roles', function ($q) {
            $q->where('name', RolesEnum::CUSTOMER->value);
        });

        // Filter by team
        if (isset($filters['team_id']) && !empty($filters['team_id'])) {
            $query->whereHas('teams', function ($q) use ($filters) {
                $q->where('teams.id', $filters['team_id']);
            });
        }

        // Search
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->get();

        // Calculate order statistics
        $customers = $customers->map(function ($customer) {
            $customer->orders_count = $customer->orders->count();
            $customer->orders_sum_total = $customer->orders->sum('total');
            return $customer;
        });

        // Sort
        if (isset($filters['sort_by'])) {
            $customers = match ($filters['sort_by']) {
                'orders' => $customers->sortByDesc('orders_count'),
                'spent' => $customers->sortByDesc('orders_sum_total'),
                'name' => $customers->sortBy('name'),
                default => $customers->sortByDesc('created_at'),
            };
        }

        return $customers->values();
    }

    /**
     * Generate team name from user's first name
     */
    private function generateTeamName(string $fullName): string
    {
        $firstName = Str::before($fullName, ' ');
        return "{$firstName}'s Store";
    }
}

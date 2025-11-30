<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Create new user
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Find user by ID
     */
    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Update user
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Attach user to team
     */
    public function attachToTeam(User $user, string $teamId): void
    {
        $user->teams()->attach($teamId);
    }

    /**
     * Set current team for user
     */
    public function setCurrentTeam(User $user, string $teamId): User
    {
        // Gunakan update biasa, tapi hindari re-trigger observer
        $user->current_team_id = $teamId;
        $user->saveQuietly(); // Hanya untuk field current_team_id

        return $user->fresh();
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, string $role): void
    {
        $user->assignRole($role);
    }

    /**
     * Get all users with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = User::query();

        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%')
                ->orWhere('email', 'like', '%' . $filters['search'] . '%');
        }

        return $query->get();
    }
}

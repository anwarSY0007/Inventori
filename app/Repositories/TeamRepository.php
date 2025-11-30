<?php

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TeamRepository
{
    /**
     * Get all teams with pagination
     */
    public function getAllTeam(array $field = ['*'], int $perPage = 25): LengthAwarePaginator
    {
        return Team::select($field)->latest()->paginate($perPage);
    }

    /**
     * Get team by slug
     */
    public function getTeamBySlug(string $slug, array $field = ['*']): ?Team
    {
        return Team::select($field)->where('slug', $slug)->first();
    }

    /**
     * Get team by ID
     */
    public function getTeamById(string $id, array $field = ['*']): ?Team
    {
        return Team::select($field)->find($id);
    }

    /**
     * Get team by keeper ID
     */
    public function getTeamByKeeperId(string $keeperId, array $field = ['*']): ?Team
    {
        return Team::select($field)->where('keeper_id', $keeperId)->first();
    }

    /**
     * Create team
     */
    public function createTeam(array $data): Team
    {
        return Team::create($data);
    }

    /**
     * Update team
     */
    public function updateTeam(Team $team, array $data): Team
    {
        $team->update($data);
        return $team->fresh();
    }

    /**
     * Delete team
     */
    public function deleteTeam(Team $team): bool
    {
        return $team->delete();
    }

    /**
     * Add member to team
     */
    public function addMember(Team $team, string $userId): void
    {
        // Check if already member
        if (!$team->users()->where('users.id', $userId)->exists()) {
            $team->users()->attach($userId);
        }
    }

    /**
     * Remove member from team
     */
    public function removeMember(Team $team, string $userId): void
    {
        $team->users()->detach($userId);
    }

    /**
     * Get team members
     */
    public function getMembers(Team $team): Collection
    {
        return $team->users; // Return Collection, bukan Query Builder
    }

    /**
     * Check if user is member of team
     */
    public function isMember(Team $team, string $userId): bool
    {
        return $team->users()->where('users.id', $userId)->exists();
    }
}

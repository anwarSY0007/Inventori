<?php

namespace App\Services;

use App\Enum\RolesEnum;
use App\Models\Team;
use App\Models\User;
use App\Repositories\TeamRepository;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamService
{
    public function __construct(
        protected TeamRepository $teamRepository
    ) {}

    /**
     * Create new team
     */
    public function create(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $team = $this->teamRepository->createTeam([
                'name' => $data['name'],
                'keeper_id' => $data['keeper_id'],
            ]);

            // Auto-add keeper as member
            $this->teamRepository->addMember($team, $data['keeper_id']);

            Log::info("Team Created: {$team->name}", ['keeper_id' => $data['keeper_id']]);

            return $team;
        });
    }

    /**
     * Update team
     */
    public function update(string $teamId, array $data): Team
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new Exception('Team not found');
        }

        $updatedTeam = $this->teamRepository->updateTeam($team, $data);

        Log::info("Team Updated: {$updatedTeam->name}", ['team_id' => $updatedTeam->id]);

        return $updatedTeam;
    }

    /**
     * Delete team
     */
    public function delete(string $teamId): bool
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new Exception('Team not found');
        }

        Log::warning("Team Deleted: {$team->name}", [
            'team_id' => $team->id,
            'deleted_by' => Auth::id()
        ]);

        return $this->teamRepository->deleteTeam($team);
    }

    /**
     * Add member to team
     */
    public function addMember(string $teamId, string $userId): void
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new Exception('Team not found');
        }

        $this->teamRepository->addMember($team, $userId);

        Log::info("Member Added to Team", [
            'team' => $team->name,
            'user_id' => $userId
        ]);
    }

    /**
     * Remove member from team
     * FIXED: Now accepts team ID instead of slug
     */
    public function removeMember(string $teamId, string $userId): void
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new Exception('Team not found');
        }

        // Prevent removing team owner
        if ($team->keeper_id === $userId) {
            throw new Exception('Cannot remove team owner from team');
        }

        $this->teamRepository->removeMember($team, $userId);

        // Clear user's current_team_id if this was their active team
        $user = User::find($userId);
        if ($user && $user->current_team_id === $teamId) {
            $user->update(['current_team_id' => null]);
        }

        Log::info("Member Removed from Team", [
            'team' => $team->name,
            'user_id' => $userId,
            'removed_by' => Auth::id()
        ]);
    }

    /**
     * Get team members (staff only - exclude customers)
     */
    public function getTeamMembers(string $teamId): Collection
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new Exception('Team not found');
        }

        // Get all members with roles loaded
        return $this->teamRepository->getMembers($team)->load('roles');
    }

    /**
     * Get team customers only
     */
    public function getTeamCustomers(string $teamId, array $filters = []): Collection
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new Exception('Team not found');
        }

        // Get members who have customer role
        $customers = $this->teamRepository->getMembers($team)
            ->load(['roles', 'orders' => function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            }])
            ->filter(function ($user) {
                return $user->hasRole(RolesEnum::CUSTOMER->value);
            });

        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $customers = $customers->filter(function ($customer) use ($search) {
                return str_contains(strtolower($customer->name), $search) ||
                    str_contains(strtolower($customer->email), $search) ||
                    str_contains(strtolower($customer->phone ?? ''), $search);
            });
        }

        // Add order statistics
        $customers = $customers->map(function ($customer) {
            $customer->orders_count = $customer->orders->count();
            $customer->orders_sum_total = $customer->orders->sum('total');
            $customer->last_order_at = $customer->orders->sortByDesc('created_at')->first()?->created_at;
            return $customer;
        });

        // Apply sorting
        if (isset($filters['sort_by'])) {
            $sortDirection = $filters['sort_direction'] ?? 'desc';

            $customers = match ($filters['sort_by']) {
                'name' => $sortDirection === 'asc'
                    ? $customers->sortBy('name')
                    : $customers->sortByDesc('name'),
                'orders' => $sortDirection === 'asc'
                    ? $customers->sortBy('orders_count')
                    : $customers->sortByDesc('orders_count'),
                'spent' => $sortDirection === 'asc'
                    ? $customers->sortBy('orders_sum_total')
                    : $customers->sortByDesc('orders_sum_total'),
                'recent' => $customers->sortByDesc('last_order_at'),
                default => $customers->sortByDesc('created_at'),
            };
        }

        return $customers->values();
    }

    /**
     * Get customer detail with order history
     */
    public function getCustomerDetail(string $teamId, string $customerId): ?User
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new Exception('Team not found');
        }

        $customer = User::with([
            'roles',
            'orders' => function ($query) use ($teamId) {
                $query->where('team_id', $teamId)
                    ->latest()
                    ->limit(50);
            }
        ])->find($customerId);

        // Verify customer belongs to this team
        if (!$customer || !$customer->teams->contains($teamId)) {
            return null;
        }

        // Verify customer has customer role
        if (!$customer->hasRole(RolesEnum::CUSTOMER->value)) {
            return null;
        }

        return $customer;
    }

    /**
     * Get team by ID
     */
    public function getTeamById(string $teamId): ?Team
    {
        return $this->teamRepository->getTeamById($teamId);
    }

    /**
     * Get all teams (Super Admin only)
     */
    public function getAllTeams(array $filters = [])
    {
        return $this->teamRepository->getAllTeam($filters);
    }
}

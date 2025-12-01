<?php

namespace App\Services;

use App\Models\Team;
use App\Repositories\TeamRepository;
use Exception;
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
            throw new \Exception('Team not found');
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
            throw new \Exception('Team not found');
        }
        Log::warning("Team Deleted: {$team->name}", ['team_id' => $team->id, 'deleted_by' => Auth::id()]);

        return $this->teamRepository->deleteTeam($team);
    }

    /**
     * Add member to team
     */
    public function addMember(string $teamId, string $userSlug): void
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new \Exception('Team not found');
        }

        $this->teamRepository->addMember($team, $userSlug);

        Log::info("Member Added to Team", ['team' => $team->name, 'user_id' => $userSlug]);
    }

    /**
     * Remove member from team
     */
    public function removeMember(string $teamSlug, string $userId): void
    {
        $team = $this->teamRepository->getTeamBySlug($teamSlug);
        if (!$team) {
            throw new Exception('Tim tidak ditemukan');
        }
        if (!$team) {
            throw new Exception('Team not found');
        }

        $this->teamRepository->removeMember($team, $userId);

        Log::info("Member Removed from Team", ['team' => $team->name, 'user_id' => $userId]);
    }
}

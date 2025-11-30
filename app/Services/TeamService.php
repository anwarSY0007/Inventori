<?php

namespace App\Services;

use App\Models\Team;
use App\Repositories\TeamRepository;
use Illuminate\Support\Facades\DB;

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

        return $this->teamRepository->updateTeam($team, $data);
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

        return $this->teamRepository->deleteTeam($team);
    }

    /**
     * Add member to team
     */
    public function addMember(string $teamId, string $userId): void
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new \Exception('Team not found');
        }

        $this->teamRepository->addMember($team, $userId);
    }

    /**
     * Remove member from team
     */
    public function removeMember(string $teamId, string $userId): void
    {
        $team = $this->teamRepository->getTeamById($teamId);

        if (!$team) {
            throw new \Exception('Team not found');
        }

        $this->teamRepository->removeMember($team, $userId);
    }
}

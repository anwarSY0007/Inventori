<?php

namespace App\Observers;

use App\Models\Team;
use App\Models\User;

class UserObserver
{
    public function created(User $user)
    {
        $teamName = strtok($user->name, " ") . "'s team";

        $team = Team::create([
            'name' => $teamName,
            'keeper_id' => $user->id
        ]);

        $user->teams()->attach($team->id);

        $user->currentTeams()->associate($team);
        $user->saveQuietly();
    }
}

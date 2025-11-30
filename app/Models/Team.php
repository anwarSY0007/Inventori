<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use UUID, HasSlug;
    protected $guarded = false;

    /**
     * Get the keeper (owner) of the team
     */
    public function keeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'keeper_id');
    }

    /**
     * Get all members of the team
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teams_users')->withTimestamps();
    }
}

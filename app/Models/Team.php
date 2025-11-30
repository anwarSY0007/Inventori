<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use UUID, HasSlug;
    protected $guarded = false;
}

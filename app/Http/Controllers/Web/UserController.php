<?php

namespace App\Http\Controllers\Web;

use App\Enum\RolesEnum;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}
    public function index(Request $req)
    {
        $filter = $req->only(['search', 'role']);

        $users = $this->userService->getAllUsers($filter, ['roles', 'currentTeam']);

        return Inertia::render('Admin/Users/index', [
            'users' => $users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'roles' => $user->roles->first()?->name ?? 'No Role',
                'role_label' => $user->roles->first()
                    ? (RolesEnum::tryFrom($user->roles->first()->name)?->label() ?? $user->roles->first()->name)
                    : '-',
                'current_teams' => $user->currentTeams ? $user->currentTeams->name : '-',

                'created_at' => $user->created_at->format('d M Y'),
            ]),
            'filters' => $req->all(['search', 'role']),
        ]);
    }
}

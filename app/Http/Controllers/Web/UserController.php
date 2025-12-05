<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AssignRoleRequest;
use App\Http\Requests\Users\RegisterMerchantRequest;
use App\Http\Requests\Users\RegisterRequest;
use App\Http\Requests\Users\SwitchTeamRequest;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display user list
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['role', 'search']);
        $users = $this->userService->getAllUsers($filters);

        $users->load(['roles', 'teams', 'currentTeams']);

        return Inertia::render('Admin/Users/index', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    /**
     * Show user profile
     */
    public function show(string $id): Response
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            abort(404, 'User not found');
        }

        $user->load(['roles', 'teams', 'currentTeams']);

        return Inertia::render('Admin/Users/detail', [
            'user' => $user->load(['teams', 'currentTeams', 'roles']),
        ]);
    }

    /**
     * Show edit profile form
     */
    public function edit(): Response
    {
        $user = Auth::user();
        $user->load(['roles', 'teams', 'currentTeams']);

        return Inertia::render('Profile/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update user profile
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $this->userService->updateProfile($user, $request->validated());

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    /**
     * Show register form
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Users/create');
    }

    /**
     * Register new user
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->userService->register($request->validated());

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Registration successful');
    }

    /**
     * Show register merchant form
     */
    public function createMerchant(): Response
    {
        return Inertia::render('Admin/Users/createMerchant');
    }

    /**
     * Register merchant with team
     */
    public function storeMerchant(RegisterMerchantRequest $request): RedirectResponse
    {
        $user = $this->userService->registerMerchantWithTeam($request->validated());

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Merchant registration successful');
    }

    /**
     * Switch user's current team
     */
    public function switchTeam(SwitchTeamRequest $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $this->userService->switchTeam($user, $request->validated()['team_id']);

            return redirect()->back()->with('success', 'Team switched successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Assign role to user (Admin only)
     */
    public function assignRole(AssignRoleRequest $request, string $userId): RedirectResponse
    {
        try {
            $user = $this->userService->getUserById($userId);

            if (!$user) {
                return redirect()->back()->with('error', 'User not found');
            }

            $this->userService->assignRole($user, $request->validated()['role']);

            return redirect()->back()->with('success', 'Role assigned successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show user profile settings page
     */
    public function profile(): Response
    {
        $user = Auth::user();

        // Load relasi
        $user->load(['roles', 'teams', 'currentTeams']);

        return Inertia::render('Profile/Show', [
            'user' => $user,
        ]);
    }
}

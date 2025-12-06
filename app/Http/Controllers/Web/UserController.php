<?php

namespace App\Http\Controllers\Web;

use App\Enum\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AssignRoleRequest;
use App\Http\Requests\Users\RegisterMerchantRequest;
use App\Http\Requests\Users\SwitchTeamRequest;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Models\Role;
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
     * Display user list - SUPER ADMIN ONLY
     * Shows ALL users across all teams
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // Only Super Admin can see all users
        if (!$user->hasRole(RolesEnum::SUPER_ADMIN->value)) {
            abort(403, 'Unauthorized access. Super Admin only.');
        }

        $filters = $request->only(['role', 'search', 'team_id']) ?? [];

        $users = $this->userService->getAllUsers($filters);
        $users->load(['roles', 'teams', 'currentTeam']);

        // Get statistics
        $stats = [
            'total_users' => $users->count(),
            'total_merchants' => $users->filter(fn($u) => $u->hasRole(RolesEnum::MERCHANT_OWNER->value))->count(),
            'total_customers' => $users->filter(fn($u) => $u->hasRole(RolesEnum::CUSTOMER->value))->count(),
            'total_staff' => $users->filter(fn($u) => $u->hasAnyRole([
                RolesEnum::ADMIN->value,
                RolesEnum::CASHIER->value,
                RolesEnum::WAREHOUSE_STAFF->value
            ]))->count(),
        ];

        return Inertia::render('Admin/Users/UsersPage', [
            'users' => $users->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'avatar' => $u->avatar,
                'role' => $u->roles->first()?->name,
                'role_label' => RolesEnum::tryFrom($u->roles->first()?->name)?->label() ?? 'Unknown',
                'current_team' => $u->currentTeam ? [
                    'id' => $u->currentTeam->id,
                    'name' => $u->currentTeam->name,
                ] : null,
                'teams_count' => $u->teams->count(),
                'created_at' => $u->created_at->format('d M Y'),
                'email_verified_at' => $u->email_verified_at?->format('d M Y'),
            ]),
            'filters' => $filters,
            'roles' => $this->getRoleOptions(),
            'stats' => $stats,
        ]);
    }

    /**
     * Display all customers across all teams - SUPER ADMIN ONLY
     */
    public function allCustomers(Request $request): Response
    {
        $user = Auth::user();

        // Only Super Admin can see all customers
        if (!$user->hasRole(RolesEnum::SUPER_ADMIN->value)) {
            abort(403, 'Unauthorized access. Super Admin only.');
        }

        $filters = $request->only(['search', 'team_id', 'sort_by']);

        $customers = $this->userService->getAllCustomers($filters);

        return Inertia::render('Admin/Customers/AllCustomers', [
            'customers' => $customers->map(fn($customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone ?? '-',
                'avatar' => $customer->avatar,
                'teams' => $customer->teams->map(fn($team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'joined_at' => $team->pivot->created_at->format('d M Y'),
                ]),
                'total_orders' => $customer->orders_count ?? 0,
                'total_spent' => $customer->orders_sum_total ?? 0,
                'registered_at' => $customer->created_at->format('d M Y'),
            ]),
            'filters' => $filters,
            'stats' => [
                'total_customers' => $customers->count(),
                'total_spent' => $customers->sum('orders_sum_total'),
            ]
        ]);
    }

    /**
     * Show user detail
     */
    public function show(string $id): Response
    {
        $currentUser = Auth::user();
        $user = $this->userService->getUserById($id);

        abort_if(!$user, 404, 'User not found');

        // Authorization: Super Admin can see all, others only in same team
        if (!$currentUser->hasRole(RolesEnum::SUPER_ADMIN->value)) {
            if (!$currentUser->teams->contains($user->current_team_id)) {
                abort(403, 'Unauthorized access');
            }
        }

        return Inertia::render('Admin/Users/UsersDetail', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'role' => $user->roles->first()?->name,
                'role_label' => RolesEnum::tryFrom($user->roles->first()?->name)?->label(),
                'current_team' => $user->currentTeam ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name,
                ] : null,
                'teams' => $user->teams->map(fn($team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'joined_at' => $team->pivot->created_at->format('d M Y'),
                ]),
                'created_at' => $user->created_at->format('d M Y H:i'),
                'email_verified_at' => $user->email_verified_at?->format('d M Y H:i'),
            ],
            'can' => [
                'edit' => $currentUser->id === $user->id || $currentUser->hasRole(RolesEnum::SUPER_ADMIN->value),
                'assign_role' => $currentUser->hasRole(RolesEnum::SUPER_ADMIN->value),
            ]
        ]);
    }

    /**
     * Show current user profile
     */
    public function profile(): Response
    {
        $user = Auth::user();

        return Inertia::render('Profile/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'role' => $user->roles->first()?->name,
                'role_label' => RolesEnum::tryFrom($user->roles->first()?->name)?->label(),
                'current_team' => $user->currentTeam ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name,
                ] : null,
                'teams' => $user->teams->map(fn($team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'is_current' => $team->id === $user->current_team_id,
                ]),
            ]
        ]);
    }

    /**
     * Show edit profile form
     */
    public function edit(): Response
    {
        $user = Auth::user();

        return Inertia::render('Profile/Edit', [
            'user' => $user->load(['roles', 'teams', 'currentTeam']),
        ]);
    }

    /**
     * Update user profile
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        try {
            $this->userService->updateProfile(Auth::user(), $request->validated());

            return back()->with('success', 'Profile updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show register merchant form (Guest only)
     */
    public function createMerchant(): Response
    {
        return Inertia::render('Admin/Auth/RegisterMerchant', [
            'roles' => $this->getMerchantRoleOptions(),
        ]);
    }

    /**
     * Register new merchant with team
     */
    public function storeMerchant(RegisterMerchantRequest $request): RedirectResponse
    {
        try {
            $user = $this->userService->registerMerchantWithTeam($request->validated());

            Auth::login($user);

            return redirect()
                ->route('dashboard')
                ->with('success', 'Merchant registration successful! Welcome to your dashboard.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Switch user's current team
     */
    public function switchTeam(SwitchTeamRequest $request): RedirectResponse
    {
        try {
            $this->userService->switchTeam(Auth::user(), $request->validated()['team_id']);

            return back()->with('success', 'Team switched successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Assign role to user (Super Admin only)
     */
    public function assignRole(AssignRoleRequest $request, string $userId): RedirectResponse
    {
        $currentUser = Auth::user();

        if (!$currentUser->hasRole(RolesEnum::SUPER_ADMIN->value)) {
            return back()->with('error', 'Unauthorized. Super Admin only.');
        }

        try {
            $user = $this->userService->getUserById($userId);

            abort_if(!$user, 404, 'User not found');

            $this->userService->assignRole($user, $request->validated()['role']);

            return back()->with('success', 'Role assigned successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get available roles for dropdown
     */
    private function getRoleOptions(): array
    {
        return Role::select(['id', 'name'])
            ->get()
            ->map(fn($role) => [
                'value' => $role->name,
                'label' => RolesEnum::tryFrom($role->name)?->label() ?? $role->name,
            ])
            ->toArray();
    }

    /**
     * Get merchant-specific roles for registration
     */
    private function getMerchantRoleOptions(): array
    {
        return [
            [
                'value' => RolesEnum::MERCHANT_OWNER->value,
                'label' => RolesEnum::MERCHANT_OWNER->label(),
            ],
            [
                'value' => RolesEnum::ADMIN->value,
                'label' => RolesEnum::ADMIN->label(),
            ],
        ];
    }
}

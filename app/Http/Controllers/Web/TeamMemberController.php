<?php

namespace App\Http\Controllers\Web;

use App\Enum\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeamMemberController extends Controller
{
    public function __construct(
        protected TeamService $teamService
    ) {}

    /**
     * Display team members (staff/admin/cashier only - not customers)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->current_team_id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki tim aktif.');
        }

        // Check authorization - only team owner or admin can view members
        if (!$user->hasAnyRole([
            RolesEnum::MERCHANT_OWNER->value,
            RolesEnum::ADMIN->value,
            RolesEnum::SUPER_ADMIN->value
        ])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        $filters = $request->only(['search', 'role']);

        try {
            // Get team members (exclude customers)
            $rawMembers = $this->teamService->getTeamMembers($user->current_team_id);
            $rawMembers->load('currentTeam');

            $members = $rawMembers
                ->where('id', '!=', $user->id)
                ->filter(function ($member) {
                    // Only show staff roles, exclude customers
                    return $member->hasAnyRole([
                        RolesEnum::ADMIN->value,
                        RolesEnum::CASHIER->value,
                        RolesEnum::WAREHOUSE_STAFF->value,
                        RolesEnum::MERCHANT_OWNER->value
                    ]);
                })
                ->when($filters['search'] ?? null, function ($collection, $search) {
                    return $collection->filter(function ($member) use ($search) {
                        return str_contains(strtolower($member->name), strtolower($search)) ||
                            str_contains(strtolower($member->email), strtolower($search));
                    });
                })
                ->when($filters['role'] ?? null, function ($collection, $role) {
                    return $collection->filter(function ($member) use ($role) {
                        return $member->hasRole($role);
                    });
                })
                ->map(fn($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'phone' => $member->phone ?? '-',
                    'avatar' => $member->avatar,
                    'role' => $member->roles->first()?->name ?? 'Staff',
                    'role_label' => $this->getRoleLabel($member->roles->first()?->name),
                    'current_teams' => $member->currentTeam ? [
                        'name' => $member->currentTeam->name
                    ] : null,
                    'created_at' => $member->created_at->format('d M Y'),
                    'joined_at' => $member->pivot->created_at->format('d M Y'),
                ])
                ->values();

            return Inertia::render('Admin/Team/TeamPages', [
                'members' => $members,
                'filters' => $filters,
                'available_roles' => [
                    ['value' => RolesEnum::CASHIER->value, 'label' => RolesEnum::CASHIER->label()],
                    ['value' => RolesEnum::WAREHOUSE_STAFF->value, 'label' => RolesEnum::WAREHOUSE_STAFF->label()],
                    ['value' => RolesEnum::ADMIN->value, 'label' => RolesEnum::ADMIN->label()],
                ],
                'can' => [
                    'create_member' => $user->hasAnyRole([RolesEnum::MERCHANT_OWNER->value, RolesEnum::ADMIN->value]),
                    'delete_member' => $user->hasAnyRole([RolesEnum::MERCHANT_OWNER->value, RolesEnum::ADMIN->value]),
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Add new team member (staff only - not customer)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Authorization check
        if (!$user->hasAnyRole([RolesEnum::MERCHANT_OWNER->value, RolesEnum::ADMIN->value])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menambahkan anggota.');
        }

        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in([
                RolesEnum::CASHIER->value,
                RolesEnum::WAREHOUSE_STAFF->value,
                RolesEnum::ADMIN->value
            ])],
        ]);

        DB::beginTransaction();
        try {
            // Create new user
            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'current_team_id' => $user->current_team_id,
                'email_verified_at' => now(),
            ]);

            // Assign role
            $newUser->assignRole($request->role);

            // Add to team
            $this->teamService->addMember($user->current_team_id, $newUser->id);

            DB::commit();

            return redirect()->back()->with('success', 'Anggota tim berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menambahkan anggota: ' . $e->getMessage());
        }
    }

    /**
     * Update team member role
     */
    public function update(Request $request, string $userId)
    {
        $user = Auth::user();

        // Authorization check
        if (!$user->hasAnyRole([RolesEnum::MERCHANT_OWNER->value, RolesEnum::ADMIN->value])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengubah role anggota.');
        }

        $request->validate([
            'role' => ['required', Rule::in([
                RolesEnum::CASHIER->value,
                RolesEnum::WAREHOUSE_STAFF->value,
                RolesEnum::ADMIN->value
            ])],
        ]);

        try {
            $member = User::findOrFail($userId);

            // Prevent changing own role
            if ($member->id === $user->id) {
                return redirect()->back()->with('error', 'Tidak dapat mengubah role diri sendiri.');
            }

            // Sync role (replace old role with new one)
            $member->syncRoles([$request->role]);

            return redirect()->back()->with('success', 'Role anggota berhasil diubah.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengubah role: ' . $e->getMessage());
        }
    }

    /**
     * Remove member from team
     */
    public function destroy(string $userId)
    {
        try {
            $currentUser = Auth::user();

            // Authorization check
            if (!$currentUser->hasAnyRole([RolesEnum::MERCHANT_OWNER->value, RolesEnum::ADMIN->value])) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus anggota.');
            }

            if ($currentUser->id === $userId) {
                return redirect()->back()->with('error', 'Tidak dapat menghapus diri sendiri.');
            }

            // Use team ID instead of slug
            $this->teamService->removeMember($currentUser->current_team_id, $userId);

            return redirect()->back()->with('success', 'Anggota tim berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus anggota: ' . $e->getMessage());
        }
    }

    private function getRoleLabel(?string $roleValue): string
    {
        return match ($roleValue) {
            RolesEnum::CASHIER->value => RolesEnum::CASHIER->label(),
            RolesEnum::WAREHOUSE_STAFF->value => RolesEnum::WAREHOUSE_STAFF->label(),
            RolesEnum::ADMIN->value => RolesEnum::ADMIN->label(),
            RolesEnum::MERCHANT_OWNER->value => RolesEnum::MERCHANT_OWNER->label(),
            default => 'Unknown',
        };
    }
}

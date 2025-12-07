<?php

namespace App\Http\Controllers\Web;

use App\Enum\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(
        protected TeamService $teamService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        // Super Admin bisa lihat semua customer di semua team
        if ($user->hasRole(RolesEnum::SUPER_ADMIN->value)) {
            return $this->indexForSuperAdmin($request);
        }

        // Owner/Admin/Staff lihat customer di team mereka
        return $this->indexForTeam($request);
    }

    private function indexForSuperAdmin(Request $request)
    {
        try {
            // Get all customers across all teams
            $customers = User::whereHas('roles', function ($query) {
                $query->where('name', RolesEnum::CUSTOMER->value);
            })
                ->with(['roles', 'teams'])
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone ?? '-',
                        'avatar' => $customer->avatar,
                        'teams' => $customer->teams->map(fn($t) => $t->name)->join(', '),
                        'total_orders' => 0,
                        'total_spent' => 0,
                        'registered_at' => $customer->created_at->format('d M Y'),
                    ];
                });

            return Inertia::render('Admin/Customers/AllCustomers', [
                'customers' => $customers,
                'team' => [
                    'id' => 'all',
                    'name' => 'All Teams (Super Admin View)',
                ],
                'stats' => [
                    'total_customers' => $customers->count(),
                ],
                'filters' => $request->only(['search']),
            ]);
        } catch (\Exception $e) {
            Log::error('CustomerController@indexForSuperAdmin error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function indexForTeam(Request $request)
    {
        $user = Auth::user();

        // Check team context
        if (!$user->current_team_id) {
            return redirect()->route('dashboard')
                ->with('error', 'No active team. Please select a team first.');
        }

        // Check authorization
        if (!$user->hasAnyRole([
            RolesEnum::MERCHANT_OWNER->value,
            RolesEnum::ADMIN->value,
            RolesEnum::CASHIER->value,
            RolesEnum::WAREHOUSE_STAFF->value
        ])) {
            return redirect()->back()
                ->with('error', 'Access denied. Required role: Owner, Admin, Cashier, or Warehouse Staff.');
        }

        try {
            $team = $user->currentTeam;

            if (!$team) {
                return redirect()->route('dashboard')
                    ->with('error', 'Team not found.');
            }

            // Get customers from current team
            $customers = $team->customers()
                ->whereHas('roles', function ($query) {
                    $query->where('name', RolesEnum::CUSTOMER->value);
                })
                ->with('roles')
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone ?? '-',
                        'avatar' => $customer->avatar,
                        'total_orders' => 0,
                        'total_spent' => 0,
                        'last_order_at' => '-',
                        'registered_at' => $customer->created_at->format('d M Y'),
                        'joined_team_at' => $customer->pivot->created_at->format('d M Y'),
                    ];
                });

            return Inertia::render('Admin/Customers/CustomerPages', [
                'customers' => $customers,
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                ],
                'stats' => [
                    'total_customers' => $customers->count(),
                ],
                'filters' => $request->only(['search']),
            ]);
        } catch (\Exception $e) {
            Log::error('CustomerController@indexForTeam error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
            ]);

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Show customer detail
     */
    public function show(string $customerId)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole([
            RolesEnum::MERCHANT_OWNER->value,
            RolesEnum::ADMIN->value,
            RolesEnum::CASHIER->value,
            RolesEnum::WAREHOUSE_STAFF->value
        ])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
        }

        try {
            $customer = $this->teamService->getCustomerDetail(
                $user->current_team_id,
                $customerId
            );

            if (!$customer) {
                return redirect()->back()->with('error', 'Pelanggan tidak ditemukan.');
            }

            return Inertia::render('Admin/Customers/Show', [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'avatar' => $customer->avatar,
                    'created_at' => $customer->created_at->format('d M Y H:i'),
                    'orders' => $customer->orders->map(fn($order) => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total' => $order->total,
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('d M Y H:i'),
                    ]),
                    'stats' => [
                        'total_orders' => $customer->orders->count(),
                        'total_spent' => $customer->orders->sum('total'),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

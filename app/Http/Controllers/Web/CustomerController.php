<?php

namespace App\Http\Controllers\Web;

use App\Enum\RolesEnum;
use App\Http\Controllers\Controller;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(
        protected TeamService $teamService
    ) {}

    /**
     * Display customers for specific team/store
     * Accessible by: MERCHANT_OWNER, ADMIN, CASHIER
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Check if user has team context
        if (!$user->current_team_id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki tim aktif.');
        }

        // Check authorization
        if (!$user->hasAnyRole([
            RolesEnum::MERCHANT_OWNER->value,
            RolesEnum::ADMIN->value,
            RolesEnum::CASHIER->value,
            RolesEnum::WAREHOUSE_STAFF->value
        ])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        $filters = $request->only(['search', 'sort_by', 'sort_direction']);

        try {
            // Get customers for current team
            $customers = $this->teamService->getTeamCustomers(
                $user->current_team_id,
                $filters
            );

            return Inertia::render('Admin/Customers/Index', [
                'customers' => $customers->map(fn($customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone ?? '-',
                    'avatar' => $customer->avatar,
                    'total_orders' => $customer->orders_count ?? 0,
                    'total_spent' => $customer->orders_sum_total ?? 0,
                    'last_order_at' => $customer->last_order_at?->format('d M Y') ?? '-',
                    'registered_at' => $customer->created_at->format('d M Y'),
                    'joined_team_at' => $customer->pivot?->created_at?->format('d M Y') ?? '-',
                ]),
                'filters' => $filters,
                'team' => [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name,
                ],
                'stats' => [
                    'total_customers' => $customers->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
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

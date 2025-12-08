<?php

namespace Database\Seeders;

use App\Enum\PaymentEnum;
use App\Enum\RolesEnum;
use App\Enum\TransactionEnum;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompleteAppSeeder extends Seeder
{
    private array $categories = [];
    private array $products = [];
    private array $warehouses = [];
    private array $merchants = [];
    private array $teams = [];

    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('🚀 Starting Complete Application Seeding...');

            // 1. Seed Categories
            $this->seedCategories();

            // 2. Seed Products
            $this->seedProducts();

            // 3. Seed Warehouses
            $this->seedWarehouses();

            // 4. Seed Warehouse Products (Initial Stock)
            $this->seedWarehouseProducts();

            // 5. Load Existing Merchants & Teams (from MerchantSeeder)
            $this->loadExistingMerchantsAndTeams();

            // 6. Seed Merchant Products (Transfer from Warehouse)
            $this->seedMerchantProducts();

            // 7. Seed Transactions
            $this->seedTransactions();

            // 8. Verify Customer-Team Relationships
            $this->verifyCustomerTeamRelationships();

            $this->command->info('✅ Complete Application Seeding Finished!');
        });
    }

    /**
     * Seed Categories
     */
    private function seedCategories(): void
    {
        $this->command->info('📦 Seeding Categories...');

        $categoriesData = [
            ['name' => 'Electronics', 'tagline' => 'Latest gadgets and devices'],
            ['name' => 'Fashion', 'tagline' => 'Trendy clothing and accessories'],
            ['name' => 'Food & Beverages', 'tagline' => 'Fresh food and drinks'],
            ['name' => 'Books & Stationery', 'tagline' => 'Reading materials and office supplies'],
            ['name' => 'Home & Living', 'tagline' => 'Furniture and home decor'],
            ['name' => 'Sports & Outdoors', 'tagline' => 'Sporting goods and outdoor gear'],
        ];

        foreach ($categoriesData as $data) {
            $category = Category::firstOrCreate(
                ['name' => $data['name']],
                [
                    'tagline' => $data['tagline'],
                    'thumbnail' => null,
                ]
            );
            $this->categories[] = $category;
            $this->command->info("   ✓ Category: {$category->name}");
        }
    }

    /**
     * Seed Products
     */
    private function seedProducts(): void
    {
        $this->command->info('📦 Seeding Products...');

        $productsData = [
            // Electronics
            ['name' => 'Samsung Galaxy S23', 'price' => 12000000, 'category' => 'Electronics', 'is_popular' => true],
            ['name' => 'iPhone 15 Pro', 'price' => 18000000, 'category' => 'Electronics', 'is_popular' => true],
            ['name' => 'Sony WH-1000XM5', 'price' => 4500000, 'category' => 'Electronics', 'is_popular' => false],
            ['name' => 'MacBook Air M2', 'price' => 16000000, 'category' => 'Electronics', 'is_popular' => true],
            ['name' => 'Dell XPS 13', 'price' => 14000000, 'category' => 'Electronics', 'is_popular' => false],

            // Fashion
            ['name' => 'Nike Air Max', 'price' => 1500000, 'category' => 'Fashion', 'is_popular' => true],
            ['name' => 'Adidas Ultraboost', 'price' => 2000000, 'category' => 'Fashion', 'is_popular' => false],
            ['name' => 'Levi\'s 501 Jeans', 'price' => 800000, 'category' => 'Fashion', 'is_popular' => true],
            ['name' => 'Uniqlo T-Shirt', 'price' => 150000, 'category' => 'Fashion', 'is_popular' => false],

            // Food & Beverages
            ['name' => 'Indomie Goreng (Box 40pcs)', 'price' => 120000, 'category' => 'Food & Beverages', 'is_popular' => true],
            ['name' => 'Nescafe Classic 200g', 'price' => 85000, 'category' => 'Food & Beverages', 'is_popular' => false],
            ['name' => 'Aqua Gallon 19L', 'price' => 20000, 'category' => 'Food & Beverages', 'is_popular' => true],

            // Books & Stationery
            ['name' => 'Harry Potter Box Set', 'price' => 1200000, 'category' => 'Books & Stationery', 'is_popular' => false],
            ['name' => 'Faber-Castell Pencil Set', 'price' => 250000, 'category' => 'Books & Stationery', 'is_popular' => true],
            ['name' => 'Moleskine Notebook', 'price' => 350000, 'category' => 'Books & Stationery', 'is_popular' => false],

            // Home & Living
            ['name' => 'IKEA Billy Bookshelf', 'price' => 1500000, 'category' => 'Home & Living', 'is_popular' => false],
            ['name' => 'Philips Air Fryer', 'price' => 1800000, 'category' => 'Home & Living', 'is_popular' => true],

            // Sports & Outdoors
            ['name' => 'Yoga Mat Premium', 'price' => 300000, 'category' => 'Sports & Outdoors', 'is_popular' => false],
            ['name' => 'Dumbbells 10kg Set', 'price' => 500000, 'category' => 'Sports & Outdoors', 'is_popular' => true],
        ];

        foreach ($productsData as $data) {
            $category = collect($this->categories)->firstWhere('name', $data['category']);

            if (!$category) {
                $this->command->warn("   ⚠ Category '{$data['category']}' not found, skipping: {$data['name']}");
                continue;
            }

            $product = Product::firstOrCreate(
                ['name' => $data['name']],
                [
                    'price' => $data['price'],
                    'category_id' => $category->id,
                    'is_popular' => $data['is_popular'],
                    'description' => "High quality {$data['name']} available now.",
                    'thumbnail' => null,
                ]
            );

            $this->products[] = $product;
            $this->command->info("   ✓ Product: {$product->name} (Rp " . number_format($product->price) . ")");
        }
    }

    /**
     * Seed Warehouses
     */
    private function seedWarehouses(): void
    {
        $this->command->info('🏭 Seeding Warehouses...');

        $warehousesData = [
            ['name' => 'Central Warehouse Jakarta', 'phone' => '021-1234567', 'alamat' => 'Jl. Sudirman No. 123, Jakarta'],
            ['name' => 'Warehouse Surabaya', 'phone' => '031-7654321', 'alamat' => 'Jl. Tunjungan No. 456, Surabaya'],
            ['name' => 'Warehouse Bandung', 'phone' => '022-9876543', 'alamat' => 'Jl. Dago No. 789, Bandung'],
        ];

        foreach ($warehousesData as $data) {
            $warehouse = Warehouse::firstOrCreate(
                ['name' => $data['name']],
                [
                    'phone' => $data['phone'],
                    'alamat' => $data['alamat'],
                    'description' => "Main storage facility in {$data['name']}",
                    'thumbnail' => null,
                ]
            );

            $this->warehouses[] = $warehouse;
            $this->command->info("   ✓ Warehouse: {$warehouse->name}");
        }
    }

    /**
     * Seed Warehouse Products (Initial Stock)
     */
    private function seedWarehouseProducts(): void
    {
        $this->command->info('📊 Seeding Warehouse Stock...');

        foreach ($this->warehouses as $warehouse) {
            $productCount = min(15, count($this->products));
            $selectedProducts = collect($this->products)->random($productCount);

            foreach ($selectedProducts as $product) {
                $stock = rand(50, 500);

                $warehouse->products()->syncWithoutDetaching([
                    $product->id => ['stock' => $stock]
                ]);

                $this->command->info("   ✓ {$warehouse->name} - {$product->name}: {$stock} units");
            }
        }
    }

    /**
     * Load existing merchants and teams (created by MerchantSeeder)
     */
    private function loadExistingMerchantsAndTeams(): void
    {
        $this->command->info('🔄 Loading Existing Merchants & Teams...');

        $this->merchants = Merchant::with('keeper')->get()->all();
        $this->teams = Team::all()->all();

        $this->command->info("   ✓ Loaded " . count($this->merchants) . " merchants");
        $this->command->info("   ✓ Loaded " . count($this->teams) . " teams");
    }

    /**
     * Seed Merchant Products (Transfer from Warehouse)
     */
    private function seedMerchantProducts(): void
    {
        $this->command->info('🏪 Seeding Merchant Stock...');
        $superAdmin = User::where('email', 'superadmin@app.com')->first();
        $adminId = $superAdmin ? $superAdmin->id : User::first()->id;

        foreach ($this->merchants as $merchant) {
            if (!$merchant) {
                continue;
            }

            // Select random warehouse
            $warehouse = collect($this->warehouses)->random();
            $warehouseProducts = $warehouse->products()
                ->wherePivot('stock', '>', 10)
                ->take(8)
                ->get();

            foreach ($warehouseProducts as $product) {
                $transferQty = rand(10, 50);
                $currentStock = $product->pivot->stock;

                if ($currentStock < $transferQty) continue;

                // Reduce warehouse stock
                $warehouse->products()->updateExistingPivot($product->id, [
                    'stock' => $currentStock - $transferQty
                ]);

                // Add to merchant
                $existingMerchantProduct = $merchant->products()->where('product_id', $product->id)->first();
                $merchantCurrentStock = $existingMerchantProduct ? $existingMerchantProduct->pivot->stock : 0;

                $merchant->products()->syncWithoutDetaching([
                    $product->id => ['stock' => $merchantCurrentStock + $transferQty]
                ]);

                StockMutation::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'type' => 'out',
                    'amount' => $transferQty,
                    'current_stock' => $currentStock - $transferQty,
                    'note' => "Transfer to {$merchant->name}",
                    'created_by' => $adminId // Superadmin
                ]);

                StockMutation::create([
                    'product_id' => $product->id,
                    'merchant_id' => $merchant->id,
                    'type' => 'in',
                    'amount' => $transferQty,
                    'current_stock' => $merchantCurrentStock + $transferQty,
                    'note' => "Received from {$warehouse->name}",
                    'created_by' => $merchant->keeper_id
                ]);

                $this->command->info("   ✓ {$merchant->name} <- {$product->name}: {$transferQty} units");
            }
        }
    }

    /**
     * Seed Transactions (Sales)
     */
    private function seedTransactions(): void
    {
        $this->command->info('💰 Seeding Transactions...');

        foreach ($this->merchants as $merchant) {
            $merchantWithProducts = Merchant::with(['products', 'keeper'])->find($merchant->id);

            if (!$merchantWithProducts || $merchantWithProducts->products->isEmpty()) {
                continue;
            }

            $team = Team::where('keeper_id', $merchant->keeper_id)->first();
            if (!$team) {
                $this->command->warn("   ⚠️ Team not found for merchant {$merchant->name}. Skipping.");
                continue;
            }

            $cashier = $team->users()->whereHas('roles', function ($q) {
                $q->where('name', RolesEnum::CASHIER->value);
            })->inRandomOrder()->first();
            $cashier = $cashier ?? $merchant->keeper;
            $customers = $team->customers()->get();
            if ($customers->isEmpty()) {
                $this->command->warn("   ⚠️ No customers in team {$team->name}. Using Guest data.");
            }

            // Create 5-10 transactions per merchant
            $transactionCount = rand(5, 10);

            for ($i = 0; $i < $transactionCount; $i++) {
                $realCustomer = $customers->isNotEmpty() && rand(0, 100) > 20 // 80% member, 20% guest
                    ? $customers->random()
                    : null;

                $this->createTransaction($merchantWithProducts, $cashier, $realCustomer);
            }
        }
    }

    /**
     * Create a single transaction
     */
    private function createTransaction(Merchant $merchant, User $cashier, ?User $customer): void
    {
        // Random customer names
        $customerName = $customer ? $customer->name : 'Guest Customer ' . Str::random(3);
        $customerPhone = $customer ? $customer->phone : '08' . rand(100000000, 999999999);
        $customerId = $customer ? $customer->id : null;

        // Select 1-3 products
        $selectedProducts = $merchant->products()
            ->wherePivot('stock', '>', 0)
            ->inRandomOrder()
            ->take(rand(1, 3))
            ->get();

        if ($selectedProducts->isEmpty()) return;

        $subTotal = 0;
        $productsData = [];

        foreach ($selectedProducts as $product) {
            $currentStock = $product->pivot->stock;
            $qty = rand(1, min(3, $currentStock)); // Jangan beli melebihi stok

            $price = $product->price;
            $subTotalItem = $price * $qty;
            $subTotal += $subTotalItem;

            $productsData[$product->id] = [
                'qty' => $qty,
                'price' => $price,
                'sub_total' => $subTotalItem
            ];

            // Kurangi Stok Merchant (Real time update)
            $merchant->products()->updateExistingPivot($product->id, [
                'stock' => $currentStock - $qty
            ]);
        }

        if (empty($productsData)) return;

        $taxTotal = (int)($subTotal * 0.1);
        $grandTotal = $subTotal + $taxTotal;

        // Random status (80% paid, 15% pending, 5% cancelled)
        $rand = rand(1, 100);
        $status = $rand <= 80 ? TransactionEnum::PAID : ($rand <= 95 ? TransactionEnum::PENDING : TransactionEnum::CANCELLED);

        $transaction = Transaction::create([
            'invoice_code' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'name' => $customerName,
            'phone' => $customerPhone,
            'sub_total' => $subTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'status' => $status,
            'payment_method' => collect([PaymentEnum::CASH, PaymentEnum::TRANSFER, PaymentEnum::QRIS])->random(),
            'payment_reference' => $status === TransactionEnum::PAID ? 'PAY-' . Str::random(8) : null,
            'paid_at' => $status === TransactionEnum::PAID ? now()->subDays(rand(0, 30)) : null,
            'merchant_id' => $merchant->id,
            'cashier_id' => $cashier->id,
            'customer_id' => $customerId
        ]);

        // Attach products
        $transaction->products()->attach($productsData);

        $this->command->info("   ✓ Transaction {$transaction->invoice_code} - Rp " . number_format($grandTotal) . " ({$status->value})");
    }

    /**
     * Verify Customer-Team Relationships
     * MerchantSeeder already handles customer-team assignment
     */
    private function verifyCustomerTeamRelationships(): void
    {
        $this->command->info('👥 Verifying Customer-Team Relationships...');

        $customers = User::whereHas('roles', function ($query) {
            $query->where('name', RolesEnum::CUSTOMER->value);
        })->count();

        $customerTeamRelations = DB::table('team_customers')->count();

        $this->command->info("   ✓ {$customers} customers found");
        $this->command->info("   ✓ {$customerTeamRelations} customer-team relationships exist");
        $this->command->info("   ℹ MerchantSeeder already assigned customers to teams");
    }
}

<?php

use App\Enum\PaymentEnum;
use App\Enum\TransactionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('invoice_code')->unique();

            $table->string('slug')->unique();
            $table->string('name')->default('Guest')->index();
            $table->string('phone')->index()->nullable();
            $table->unsignedBigInteger('sub_total');
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('grand_total');

            $table->enum('status', array_column(TransactionEnum::cases(), 'value'))
                ->default(TransactionEnum::PENDING->value)
                ->index();
            $table->string('payment_method')
                ->nullable()
                ->default(PaymentEnum::CASH->value);
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('customer_id')
                ->nullable()
                ->index()
                ->constrained('users')
                ->nullOnDelete();

            $table->index('invoice_code');
            $table->index(['merchant_id', 'status', 'created_at']);
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE transactions ADD CONSTRAINT check_grand_total_valid CHECK (grand_total >= sub_total)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

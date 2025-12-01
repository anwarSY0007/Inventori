<?php

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
        Schema::create('stock_mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->nullable()->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUuid('merchant_id')->nullable()->constrained('merchants')->cascadeOnDelete();

            $table->enum('type', ['in', 'out'])->index();
            $table->unsignedInteger('amount');
            $table->integer('current_stock');

            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->index(['reference_type', 'reference_id']);
            $table->text('note')->nullable();

            $table->foreignUuid('created_by')->constrained('users');
            $table->index(['product_id', 'created_at']); // History by product
            $table->index(['warehouse_id', 'type', 'created_at']); // Warehouse reports
            $table->index(['merchant_id', 'type', 'created_at']); // Merchant reports
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE stock_mutations ADD CONSTRAINT check_mutation_stock_non_negative CHECK (current_stock >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};

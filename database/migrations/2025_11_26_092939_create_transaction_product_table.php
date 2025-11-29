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
        Schema::create('transaction_product', function (Blueprint $table) {
            $table->foreignUuid('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->foreignUuid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->integer('qty');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('sub_total');

            $table->primary(['transaction_id', 'product_id']);

            $table->index('product_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_product');
    }
};

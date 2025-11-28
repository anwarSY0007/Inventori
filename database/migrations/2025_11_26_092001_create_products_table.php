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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('slug')->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->string('thumbnail')->nullable();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_popular')->default(false);

            $table->index(['name','price','is_popular']);
            $table->softDeletes();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

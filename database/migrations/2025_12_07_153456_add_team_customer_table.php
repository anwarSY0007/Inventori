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
        Schema::create('team_customers', function (Blueprint $table) {
            $table->foreignUuid('team_id')
                ->constrained('teams')
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);

            $table->index(['team_id', 'created_at']); // History by product
            $table->index(['user_id', 'created_at']); // Warehouse reports
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_customers');
    }
};

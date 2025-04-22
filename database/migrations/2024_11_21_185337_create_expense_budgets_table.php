<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('expense_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('expense_budget_versions')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->foreignId('category_id')->constrained('categories');
            $table->decimal('january_amount', 15, 2)->default(0);
            $table->decimal('february_amount', 15, 2)->default(0);
            $table->decimal('march_amount', 15, 2)->default(0);
            $table->decimal('april_amount', 15, 2)->default(0);
            $table->decimal('may_amount', 15, 2)->default(0);
            $table->decimal('june_amount', 15, 2)->default(0);
            $table->decimal('july_amount', 15, 2)->default(0);
            $table->decimal('august_amount', 15, 2)->default(0);
            $table->decimal('september_amount', 15, 2)->default(0);
            $table->decimal('october_amount', 15, 2)->default(0);
            $table->decimal('november_amount', 15, 2)->default(0);
            $table->decimal('december_amount', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['version_id', 'cost_center_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_budgets');
    }
};

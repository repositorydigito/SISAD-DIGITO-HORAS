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
        Schema::create('expense_budget_versions', function (Blueprint $table) {
            $table->id();
            $table->integer('version_number');
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->integer('year');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['year', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_budget_versions');
    }
};

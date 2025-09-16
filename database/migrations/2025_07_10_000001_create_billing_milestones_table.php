<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('name', 255);
            $table->date('planned_date');
            $table->date('real_date')->nullable();
            $table->decimal('progress', 5, 2)->default(0); // 0.00 - 1.00 (0% - 100%)
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('status', 50);
            $table->text('comments')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_milestones');
    }
};

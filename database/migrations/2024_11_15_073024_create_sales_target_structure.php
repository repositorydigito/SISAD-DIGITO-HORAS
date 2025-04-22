<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para los años
        Schema::create('sales_target_years', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->timestamps();
            $table->unique('year');
        });

        // Tabla para las versiones
        Schema::create('sales_target_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained('sales_target_years')->cascadeOnDelete();
            $table->integer('version_number');
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->unique(['year_id', 'version_number']);
        });

        // Tabla para los montos
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('sales_target_versions')->cascadeOnDelete();
            $table->foreignId('business_line_id')->constrained('business_lines')->cascadeOnDelete();
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
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['version_id', 'business_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
        Schema::dropIfExists('sales_target_versions');
        Schema::dropIfExists('sales_target_years');
    }
};
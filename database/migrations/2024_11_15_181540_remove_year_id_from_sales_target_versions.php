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
        Schema::table('sales_target_versions', function (Blueprint $table) {
            // Verificamos si la columna existe antes de intentar eliminarla
            if (Schema::hasColumn('sales_target_versions', 'year_id')) {
                $table->dropColumn('year_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_target_versions', function (Blueprint $table) {
            // Si necesitas recrear la columna en el rollback
            if (!Schema::hasColumn('sales_target_versions', 'year_id')) {
                $table->unsignedBigInteger('year_id')->after('id');
            }
        });
    }
};

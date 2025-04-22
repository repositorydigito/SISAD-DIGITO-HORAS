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
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('category', ['Categoria1', 'Categoria2', 'Categoria3', 'Categoria4'])
                  ->nullable()
                  ->after('business_line_id');
                  
            $table->enum('state', ['Activo', 'Inactivo', 'Completado', 'Suspendido'])
                  ->nullable()
                  ->after('category');
                  
            $table->date('end_date_projected')
                  ->nullable()
                  ->after('end_date');
                  
            $table->decimal('real_progress', 5, 2)
                  ->nullable()
                  ->after('end_date_projected');
                  
            $table->enum('phase', ['Inicio', 'Planificación', 'Ejecución', 'Control', 'Cierre'])
                  ->nullable()
                  ->after('real_progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'state',
                'end_date_projected',
                'real_progress',
                'phase'
            ]);
        });
    }
};

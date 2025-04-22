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
        Schema::table('time_entries', function (Blueprint $table) {
            // Agregamos el campo phase después de hours
            $table->enum('phase', ['inicio', 'planificacion', 'ejecucion', 'control', 'cierre'])
                ->after('hours');

            // Renombramos entry_date a date para mantener consistencia
            $table->renameColumn('entry_date', 'date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            // Eliminamos la columna phase
            $table->dropColumn('phase');

            // Revertimos el cambio de nombre de la columna
            $table->renameColumn('date', 'entry_date');
        });
    }
};

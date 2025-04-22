<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessLinesAndUpdateProjects extends Migration
{
    public function up(): void
    {
        // Crear la nueva tabla business_lines
        Schema::create('business_lines', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Modificar la tabla projects
        Schema::table('projects', static function (Blueprint $table): void {
            // Eliminar la columna existente
            $table->dropColumn('business_line');
            
            // Agregar la nueva columna de relación
            $table->foreignId('business_line_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Revertir los cambios en la tabla projects
        Schema::table('projects', static function (Blueprint $table): void {
            $table->dropForeignId('business_line_id');
            $table->string('business_line')->nullable();
        });

        // Eliminar la tabla business_lines
        Schema::dropIfExists('business_lines');
    }
}
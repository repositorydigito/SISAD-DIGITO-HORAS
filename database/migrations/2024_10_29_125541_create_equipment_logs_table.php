<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquipmentLogsTable extends Migration
{
    public function up()
    {
        Schema::create('equipment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade'); // Relación con projects
            $table->date('date')->comment('Fecha');
            $table->decimal('diesel_gal', 8, 2)->nullable()->comment('Cantidad de Diesel en galones');
            $table->time('start_time')->nullable()->comment('Hora de Inicio');
            $table->time('end_time')->nullable()->comment('Hora de Fin');
            $table->decimal('engine_hours', 5, 2)->nullable()->comment('Horas motor en trabajo');
            $table->string('equipment', 255)->nullable()->comment('Equipo');
            $table->decimal('delay_hours', 5, 2)->nullable()->comment('Horas de demora');
            $table->string('delay_activity', 255)->nullable()->comment('Actividad de demora');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('equipment_logs');
    }
}

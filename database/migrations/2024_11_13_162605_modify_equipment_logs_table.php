<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyEquipmentLogsTable extends Migration
{
    public function up()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment_logs', 'equipment_id')) {
                $table->foreignId('equipment_id')
                    ->after('project_id')
                    ->references('id')->on('equipments') // Cambiado a 'equipments'
                    ->constrained('equipments')          // Cambiado a 'equipments'
                    ->onDelete('cascade')
                    ->onUpdate('no action');
            }

            if (Schema::hasColumn('equipment_logs', 'equipment')) {
                $table->dropColumn('equipment');
            }
        });
    }

    public function down()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment_logs', 'equipment')) {
                $table->string('equipment')->nullable()->comment('Equipo')->after('engine_hours');
            }

            if (Schema::hasColumn('equipment_logs', 'equipment_id')) {
                $table->dropForeign(['equipment_id']);
                $table->dropColumn('equipment_id');
            }
        });
    }
}

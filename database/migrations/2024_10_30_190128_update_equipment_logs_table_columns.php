<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            // Verificar y modificar cada columna solo si no tiene las especificaciones ya aplicadas
            if (!DB::getSchemaBuilder()->getColumnType('equipment_logs', 'start_time') === 'double') {
                $table->double('start_time')
                    ->nullable()
                    ->default(null)
                    ->comment('Hora de Inicio')
                    ->change();
            }

            if (!DB::getSchemaBuilder()->getColumnType('equipment_logs', 'end_time') === 'double') {
                $table->double('end_time')
                    ->nullable()
                    ->default(null)
                    ->comment('Hora de Fin')
                    ->change();
            }

            if (!DB::getSchemaBuilder()->getColumnType('equipment_logs', 'engine_hours') === 'double') {
                $table->double('engine_hours')
                    ->nullable()
                    ->default(null)
                    ->comment('Horas motor en trabajo')
                    ->change();
            }

            if (!DB::getSchemaBuilder()->getColumnType('equipment_logs', 'delay_hours') === 'double') {
                $table->double('delay_hours')
                    ->nullable()
                    ->default(null)
                    ->comment('Horas de demora')
                    ->change();
            }

            if (!DB::getSchemaBuilder()->getColumnType('equipment_logs', 'delay_activity') === 'enum') {
                $table->enum('delay_activity', [
                    'CALENTAMIENTO',
                    'TRASLADO_EQUIPO',
                    'MANTENIMIENTO_PREVIO',
                    'MANTENIMIENTO_PROGRAMADO',
                    'HORAS_MOTOR_MANTENIMIENTO',
                    'HORAS_MOTOR_MANTENIMIENTO_NO_PROGRAMADO'
                ])
                    ->nullable()
                    ->default(null)
                    ->comment('Actividad de demora')
                    ->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            // Revert changes here if needed, according to the original structure.
        });
    }
};

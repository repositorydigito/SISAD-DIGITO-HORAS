<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            $table->decimal('initial_mileage', 10, 2)
                ->nullable()
                ->after('delay_activity')
                ->comment('Kilometraje Inicial');
                
            $table->decimal('final_mileage', 10, 2)
                ->nullable()
                ->after('initial_mileage')
                ->comment('Kilometraje Final');
                
            $table->decimal('tons', 8, 2)
                ->nullable()
                ->after('final_mileage')
                ->comment('Toneladas');
        });
    }

    public function down()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            $table->dropColumn(['initial_mileage', 'final_mileage', 'tons']);
        });
    }
};
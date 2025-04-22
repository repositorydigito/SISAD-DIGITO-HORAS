<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            // Solo agregamos el índice
            $table->index('equipment_id');
        });
    }

    public function down()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            $table->dropIndex(['equipment_id']);
        });
    }
};

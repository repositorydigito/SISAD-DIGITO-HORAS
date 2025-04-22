<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales_target_versions', function (Blueprint $table) {
            $table->integer('year')->nullable(); // O ajusta el tipo de dato y restricciones según sea necesario
        });
    }
    
    public function down()
    {
        Schema::table('sales_target_versions', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};

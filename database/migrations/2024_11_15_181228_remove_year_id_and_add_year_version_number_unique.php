<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales_target_versions', function (Blueprint $table) {
            // Primero eliminamos la llave foránea que usa year_id
            $table->dropForeign(['year_id']);

            // Ahora podemos eliminar el índice único
            $table->dropUnique('sales_target_versions_year_id_version_number_unique');

            // Eliminamos la columna year_id si ya no la necesitas
            $table->dropColumn('year_id');

            // Creamos el nuevo índice único
            $table->unique(['year', 'version_number'], 'sales_target_versions_year_version_number_unique');
        });
    }

    public function down()
    {
        Schema::table('sales_target_versions', function (Blueprint $table) {
            // Eliminamos el nuevo índice único
            $table->dropUnique('sales_target_versions_year_version_number_unique');

            // Recreamos la columna year_id
            $table->unsignedBigInteger('year_id')->after('id');

            // Recreamos el índice único original
            $table->unique(['year_id', 'version_number'], 'sales_target_versions_year_id_version_number_unique');

            // Recreamos la llave foránea
            $table->foreign('year_id')
                ->references('id')
                ->on('sales_target_years');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostCenterIdToCategoriesTable extends Migration
{
    
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
      
            $table->unsignedBigInteger('cost_center_id')->nullable()->after('id'); 
            $table->foreign('cost_center_id')
                  ->references('id')
                  ->on('cost_centers')
                  ->onDelete('set null'); 
        });
    }

    
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
            $table->dropColumn('cost_center_id');
        });
    }
}
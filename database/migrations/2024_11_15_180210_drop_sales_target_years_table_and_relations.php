<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
   {
       Schema::disableForeignKeyConstraints();
       Schema::dropIfExists('sales_target_years');
       Schema::enableForeignKeyConstraints();
   }

   public function down()
   {
       Schema::create('sales_target_years', function (Blueprint $table) {
           $table->id();
           $table->integer('year')->unique();
           $table->enum('status', ['draft', 'approved'])->default('draft');
           $table->timestamps();
       }); 
   }
};
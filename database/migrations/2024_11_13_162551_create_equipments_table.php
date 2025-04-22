<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquipmentsTable extends Migration
{
    public function up()
    {
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vehicle_type', 100);
            $table->foreignId('entity_id')
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('no action');
            $table->string('driver');
            $table->string('license', 50);
            $table->string('plate_number1', 20);
            $table->string('plate_number2', 20)->nullable();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('no action');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('no action');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('equipments');
    }
}

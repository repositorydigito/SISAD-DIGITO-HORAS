<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntitiesTable extends Migration
{
    public function up()
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['Client', 'Supplier', 'Payroll']);
            $table->string('business_name', 255);
            $table->string('trade_name', 255)->nullable();
            $table->string('tax_id', 20)->unique();
            $table->string('business_group', 255)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('copy_email', 255)->nullable();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('account_number', 50)->nullable();
            $table->string('interbank_account_number', 50)->nullable();
            $table->string('detraccion_account_number', 50)->nullable();
            $table->string('reference_recommendation', 255)->nullable();
            $table->integer('credit_days')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('entities');
    }
}

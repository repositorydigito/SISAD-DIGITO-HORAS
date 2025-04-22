<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomesTable extends Migration
{
    public function up()
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('entity_id')->constrained('entities')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');

            // Datos principales
            $table->enum('document_type', ['Boleta de venta', 'Factura', 'Nota de abono', 'Nota de débito', 'Valor residual']);
            $table->string('document_number', 50);
            $table->date('document_date');
            $table->string('description', 255)->nullable();

            // Información de moneda y montos
            $table->enum('currency', ['Soles', 'Dólares']);
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->decimal('amount_pen', 15, 2)->nullable();

            // Información de pago y estado
            $table->date('payment_plan_date')->nullable();
            $table->date('real_payment_date')->nullable();
            $table->enum('status', ['Por Revisar', 'Por Facturar', 'Por Cobrar', 'Cobrado', 'Suspendido', 'Provisionado'])->default('Por Revisar');
            $table->decimal('service_percentage', 5, 2)->nullable();
            $table->decimal('deposit_amount', 15, 2)->nullable();
            $table->decimal('detraccion_amount', 15, 2)->nullable();
            $table->boolean('is_accounted')->default(false);
            $table->text('observations')->nullable();

            // Documento adjunto
            $table->string('attachment_path', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('incomes');
    }
}

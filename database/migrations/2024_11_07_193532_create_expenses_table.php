<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->enum('document_type', [
                'Recibo por Honorarios',
                'Recibo de Compra',
                'Nota de crédito',
                'Boleta de pago',
                'Nota de Pago',
                'Sin Documento',
                'Ticket'
            ]);
            $table->string('document_number', 50)->nullable();
            $table->date('document_date')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('remark', 255)->nullable();
            $table->enum('currency', ['USD', 'PEN']);
            $table->decimal('amount_usd', 10, 2)->nullable();
            $table->decimal('amount_pen', 10, 2)->nullable();
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('withholding_amount', 10, 2)->nullable();
            $table->enum('status', [
                'por revisar',
                'por pagar',
                'por pagar detraccion',
                'por reembolsar',
                'pagado'
            ]);
            $table->enum('payment_status', ['pendiente', 'pagado', 'anulado'])->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->date('planned_payment_date')->nullable();
            $table->date('actual_payment_date')->nullable();
            $table->enum('expense_type', ['fijo', 'variable'])->nullable();
            $table->decimal('amount_to_pay', 10, 2)->nullable();
            $table->unsignedBigInteger('responsible_id')->nullable();
            $table->boolean('has_attachment')->nullable();
            $table->text('observations')->nullable();
            $table->boolean('accounting')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('entity_id')->references('id')->on('entities')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expenses');
    }
};


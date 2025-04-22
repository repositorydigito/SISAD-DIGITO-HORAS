<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('end_date_real')
                  ->nullable()
                  ->after('end_date_projected');
                  
            $table->text('description_incidence')
                  ->nullable()
                  ->after('real_progress');
                  
            $table->enum('reason_incidence', ['Clima', 'Falta de materiales', 'Problemas técnicos', 'Problemas administrativos', 'Otros'])
                  ->nullable()
                  ->after('description_incidence');
                  
            $table->text('description_risk')
                  ->nullable()
                  ->after('reason_incidence');
                  
            $table->enum('state_risk', ['Alto', 'Medio', 'Bajo', 'Controlado'])
                  ->nullable()
                  ->after('description_risk');
                  
            $table->text('description_change_control')
                  ->nullable()
                  ->after('state_risk');
                  
            $table->decimal('billing', 5, 2)
                  ->nullable()
                  ->after('description_change_control');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'end_date_real',
                'description_incidence',
                'reason_incidence',
                'description_risk',
                'state_risk',
                'description_change_control',
                'billing'
            ]);
        });
    }
};

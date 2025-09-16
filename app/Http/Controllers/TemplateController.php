<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class TemplateController extends Controller
{
    public function downloadTimeEntriesTemplate()
    {
        // Usar punto y coma como separador para compatibilidad con Excel en español
        // Incluir fechas en formato peruano (dd/mm/yyyy)
        $content = "project_id;user_id;date;hours;phase;description\n";
        $content .= "1;2;02/12/2024;1.50;planificacion;\"Reunión de planificación del proyecto\"\n";
        $content .= "1;2;03/12/2024;4.00;ejecucion;\"Desarrollo de funcionalidad principal\"\n";
        $content .= "2;1;03/12/2024;2.50;inicio;\"Kickoff del proyecto con el cliente\"\n";
        $content .= "3;3;04/12/2024;6.00;control;\"Revisión de avances y correcciones\"\n";
        $content .= "2;2;05/12/2024;3.75;cierre;\"Documentación final y entrega\"";

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="plantilla_registros_tiempo.csv"');
    }
}

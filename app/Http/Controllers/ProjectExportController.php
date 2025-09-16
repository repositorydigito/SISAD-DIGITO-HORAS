<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ProjectExportController extends Controller
{
    public function export(Request $request)
    {
        // Obtener proyectos (todos o seleccionados)
        $query = Project::query();

        // Si se proporcionan IDs específicos, filtrar por ellos
        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $query->whereIn('id', $ids);
        }

        // Cargar relaciones necesarias
        $query->with(['entity', 'businessLine', 'users']);

        // Obtener proyectos
        $projects = $query->get();

        // Crear CSV (solución más simple que Excel)
        $headers = [
            'ID', 'Nombre', 'Código', 'Entidad', 'Línea de Negocio',
            'Categoría', 'Estado', 'Fase', 'Fecha Inicio', 'Fecha Fin',
            'Progreso Real (%)', 'Facturación (%)', 'Usuarios Asignados'
        ];

        $callback = function() use ($projects, $headers) {
            $file = fopen('php://output', 'w');

            // Escribir encabezados
            fputcsv($file, $headers);

            // Escribir datos
            foreach ($projects as $project) {
                $row = [
                    $project->id,
                    $project->name,
                    $project->code,
                    $project->entity->business_name ?? '',
                    $project->businessLine->name ?? '',
                    $project->category,
                    $project->state,
                    $project->phase,
                    $project->start_date,
                    $project->end_date,
                    $project->real_progress,
                    $project->billing,
                    $project->users->pluck('name')->join(', ')
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        // Crear respuesta HTTP
        $fileName = 'proyectos-' . date('Y-m-d') . '.csv';

        // Devolver respuesta con el archivo CSV
        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}

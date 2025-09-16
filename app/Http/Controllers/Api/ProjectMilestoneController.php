<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectMilestoneController extends Controller
{
    /**
     * Obtener todos los hitos de un proyecto
     *
     * @param Project $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Project $project)
    {
        $milestones = $project->milestones;

        return response()->json([
            'data' => $milestones->map(function ($milestone) {
                // Calcular las horas totales para este hito (usando el atributo calculado)
                $totalHours = $milestone->total_hours;

                return [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'description' => $milestone->description,
                    'start_date' => $milestone->start_date,
                    'end_date' => $milestone->end_date,
                    'billing_percentage' => $milestone->billing_percentage / 100,
                    'status' => $milestone->status,
                    'progress' => (float) $milestone->progress,
                    'is_paid' => (bool) $milestone->is_paid,
                    'order' => $milestone->order,
                    'total_hours' => (float) $totalHours,
                ];
            })
        ]);
    }

    /**
     * Almacenar un nuevo hito
     *
     * @param Request $request
     * @param Project $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'billing_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:Pendiente,En Progreso,Completado,Retrasado',
            'progress' => 'nullable|numeric|min:0|max:100',
            'is_paid' => 'boolean',
        ]);

        // Verificar que las fechas estén dentro del rango del proyecto
        if ($project->start_date && $validated['start_date'] < $project->start_date) {
            return response()->json([
                'message' => 'La fecha de inicio del hito no puede ser anterior a la fecha de inicio del proyecto',
                'errors' => ['start_date' => ['La fecha de inicio del hito debe ser posterior o igual a la fecha de inicio del proyecto']]
            ], 422);
        }

        if ($project->end_date && $validated['end_date'] > $project->end_date) {
            return response()->json([
                'message' => 'La fecha de fin del hito no puede ser posterior a la fecha de fin del proyecto',
                'errors' => ['end_date' => ['La fecha de fin del hito debe ser anterior o igual a la fecha de fin del proyecto']]
            ], 422);
        }

        // Obtener el último orden
        $lastOrder = $project->milestones()->max('order') ?? 0;

        // Crear el hito
        $milestone = $project->milestones()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'billing_percentage' => $validated['billing_percentage'],
            'status' => $validated['status'],
            'progress' => isset($validated['progress']) ? $validated['progress'] / 100 : 0,
            'order' => $lastOrder + 1,
        ]);

        return response()->json([
            'message' => 'Hito creado correctamente',
            'data' => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'description' => $milestone->description,
                'start_date' => $milestone->start_date,
                'end_date' => $milestone->end_date,
                'billing_percentage' => $milestone->billing_percentage / 100,
                'status' => $milestone->status,
                'progress' => (float) $milestone->progress,
                'is_paid' => (bool) $milestone->is_paid,
                'order' => $milestone->order,
                'total_hours' => 0, // Nuevo hito, no tiene horas registradas
            ]
        ], 201);
    }

    /**
     * Mostrar un hito específico
     *
     * @param Project $project
     * @param ProjectMilestone $milestone
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Project $project, ProjectMilestone $milestone)
    {
        // Verificar que el hito pertenezca al proyecto
        if ($milestone->project_id !== $project->id) {
            return response()->json(['message' => 'El hito no pertenece al proyecto especificado'], 404);
        }

        // Calcular las horas totales para este hito (usando el atributo calculado)
        $totalHours = $milestone->total_hours;

        return response()->json([
            'data' => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'description' => $milestone->description,
                'start_date' => $milestone->start_date,
                'end_date' => $milestone->end_date,
                'billing_percentage' => $milestone->billing_percentage / 100,
                'status' => $milestone->status,
                'progress' => (float) $milestone->progress,
                'is_paid' => (bool) $milestone->is_paid,
                'order' => $milestone->order,
                'total_hours' => (float) $totalHours,
            ]
        ]);
    }

    /**
     * Actualizar un hito específico
     *
     * @param Request $request
     * @param Project $project
     * @param ProjectMilestone $milestone
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Project $project, ProjectMilestone $milestone)
    {
        // Verificar que el hito pertenezca al proyecto
        if ($milestone->project_id !== $project->id) {
            return response()->json(['message' => 'El hito no pertenece al proyecto especificado'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'billing_percentage' => 'sometimes|required|numeric|min:0|max:100',
            'status' => 'sometimes|required|in:Pendiente,En Progreso,Completado,Retrasado',
            'progress' => 'sometimes|nullable|numeric|min:0|max:100',
            'is_paid' => 'sometimes|boolean',
            'order' => 'sometimes|required|integer|min:1',
        ]);

        // Convertir progreso de porcentaje a decimal si está presente
        if (isset($validated['progress'])) {
            $validated['progress'] = $validated['progress'] / 100;
        }

        // Actualizar el hito
        $milestone->update($validated);

        // Calcular total de horas
        $totalHours = $milestone->total_hours;

        return response()->json([
            'message' => 'Hito actualizado correctamente',
            'data' => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'description' => $milestone->description,
                'start_date' => $milestone->start_date,
                'end_date' => $milestone->end_date,
                'billing_percentage' => $milestone->billing_percentage / 100,
                'status' => $milestone->status,
                'progress' => (float) $milestone->progress,
                'is_paid' => (bool) $milestone->is_paid,
                'order' => $milestone->order,
                'total_hours' => (float) $totalHours,
            ]
        ]);
    }

    /**
     * Eliminar un hito específico
     *
     * @param Project $project
     * @param ProjectMilestone $milestone
     * @return Response
     */
    public function destroy(Project $project, ProjectMilestone $milestone)
    {
        // Verificar que el hito pertenezca al proyecto
        if ($milestone->project_id !== $project->id) {
            return response()->json(['message' => 'El hito no pertenece al proyecto especificado'], 404);
        }

        $milestone->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TimeEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TimeEntry::with(['user', 'project', 'milestone']);

        // Filtro por fecha
        if ($request->has('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        // Filtro por usuario
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtro por proyecto
        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Filtro por rango de fechas
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
        }

        // Ordenar por fecha descendente por defecto
        $query->orderBy('date', 'desc');

        // Paginación
        $perPage = $request->input('per_page', 15);
        $timeEntries = $query->paginate($perPage);

        return TimeEntryResource::collection($timeEntries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'project_id' => 'required|exists:projects,id',
                'milestone_id' => 'nullable|exists:project_milestones,id',
                'date' => 'required|date',
                'phase' => 'required|in:' . implode(',', array_keys(TimeEntry::PHASES)),
                'hours' => 'required|numeric|min:0.1|max:24',
                'detail' => 'nullable|string|max:1000',
                'description' => 'nullable|string|max:1000'
            ]);

            $timeEntry = TimeEntry::create($validated);
            $timeEntry->load(['user', 'project', 'milestone']);

            return response()->json([
                'message' => 'Registro de tiempo creado exitosamente',
                'data' => new TimeEntryResource($timeEntry)
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TimeEntry $timeEntry)
    {
        $timeEntry->load(['user', 'project', 'milestone']);
        return new TimeEntryResource($timeEntry);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'project_id' => 'sometimes|exists:projects,id',
                'milestone_id' => 'nullable|exists:project_milestones,id',
                'date' => 'sometimes|date',
                'phase' => 'sometimes|in:' . implode(',', array_keys(TimeEntry::PHASES)),
                'hours' => 'sometimes|numeric|min:0.1|max:24',
                'description' => 'nullable|string|max:1000'
            ]);

            $timeEntry->update($validated);
            $timeEntry->load(['user', 'project', 'milestone']);

            return response()->json([
                'message' => 'Registro de tiempo actualizado exitosamente',
                'data' => new TimeEntryResource($timeEntry)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeEntry $timeEntry): JsonResponse
    {
        try {
            $timeEntry->delete();

            return response()->json([
                'message' => 'Registro de tiempo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

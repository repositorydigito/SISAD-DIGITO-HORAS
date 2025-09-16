<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    /**
     * Obtener listado de proyectos
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // Filtrar por fechas si se proporcionan
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = Project::query()->with([
                'entity',
                'businessLine',
                'creator',
                'modifier',
                'milestones',
                'users' => function ($userQuery) use ($startDate, $endDate) {
                    $userQuery->withSum(['timeEntries' => function ($timeQuery) use ($startDate, $endDate) {
                        $timeQuery->whereBetween('date', [$startDate, $endDate]);
                    }], 'hours');
                }
            ]);

            // Añadir información de horas en el rango de fechas
            $query->withCount(['timeEntries as total_hours_in_range' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                      ->select(\DB::raw('SUM(hours)'));
            }]);
        } else {
            $query = Project::query()->with([
                'entity',
                'businessLine',
                'creator',
                'modifier',
                'milestones',
                'users'
            ]);

            // Añadir información de horas totales
            $query->withCount(['timeEntries as total_hours' => function ($query) {
                $query->select(\DB::raw('SUM(hours)'));
            }]);
        }

        // Filtros
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('state')) {
            $query->where('state', $request->state);
        }

        if ($request->has('phase')) {
            $query->where('phase', $request->phase);
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->has('business_line_id')) {
            $query->where('business_line_id', $request->business_line_id);
        }

        if ($request->has('start_date_from')) {
            $query->where('start_date', '>=', $request->start_date_from);
        }

        if ($request->has('start_date_to')) {
            $query->where('start_date', '<=', $request->start_date_to);
        }

        if ($request->has('end_date_from')) {
            $query->where('end_date', '>=', $request->end_date_from);
        }

        if ($request->has('end_date_to')) {
            $query->where('end_date', '<=', $request->end_date_to);
        }

        // Ordenamiento
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginación
        $perPage = $request->input('per_page', 15);
        $projects = $query->paginate($perPage);

        // Cargar horas específicas por proyecto para cada usuario
        foreach ($projects as $project) {
            $project->load(['users' => function ($userQuery) use ($project) {
                $userQuery->withSum(['timeEntries' => function ($timeQuery) use ($project) {
                    $timeQuery->where('project_id', $project->id);
                }], 'hours');
            }]);
        }

        return ProjectResource::collection($projects);
    }

    /**
     * Obtener un proyecto específico
     *
     * @param Project $project
     * @return ProjectResource
     */
    public function show(Request $request, Project $project)
    {
        // Cargar relaciones básicas
        $relations = ['entity', 'businessLine', 'users', 'creator', 'modifier', 'milestones'];

        // Filtrar por fechas si se proporcionan
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Añadir información de horas en el rango de fechas
            $project->loadCount(['timeEntries as total_hours_in_range' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                      ->select(DB::raw('SUM(hours)'));
            }]);

            // Cargar usuarios con sus horas en el rango de fechas
            $project->load(['users' => function ($query) use ($project, $startDate, $endDate) {
                $query->withSum(['timeEntries' => function ($subQuery) use ($project, $startDate, $endDate) {
                    $subQuery->where('project_id', $project->id)
                             ->whereBetween('date', [$startDate, $endDate]);
                }], 'hours');
            }]);

            // Cargar hitos con sus horas en el rango de fechas
            $project->load(['milestones' => function ($query) use ($startDate, $endDate) {
                $query->withCount(['timeEntries as total_hours_in_range' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate])
                          ->select(DB::raw('SUM(hours)'));
                }]);
            }]);
        } else {
            // Cargar relaciones con horas totales
            $project->loadCount(['timeEntries as total_hours' => function ($query) {
                $query->select(DB::raw('SUM(hours)'));
            }]);

            // Cargar todas las relaciones incluyendo usuarios con sus horas específicas del proyecto
            $project->load([
                'entity',
                'businessLine',
                'creator',
                'modifier',
                'milestones',
                'users' => function ($query) use ($project) {
                    $query->withSum(['timeEntries' => function ($subQuery) use ($project) {
                        $subQuery->where('project_id', $project->id);
                    }], 'hours');
                }
            ]);
        }

        return new ProjectResource($project);
    }

    /**
     * Crear un nuevo proyecto
     *
     * @param Request $request
     * @return ProjectResource
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:projects',
            'entity_id' => 'required|exists:entities,id',
            'business_line_id' => 'nullable|exists:business_lines,id',
            'category' => 'nullable|string',
            'validity' => 'nullable|string|in:Vigente,Sin Vigencia',
            'state' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_date_projected' => 'nullable|date|after_or_equal:start_date',
            'end_date_real' => 'nullable|date|after_or_equal:start_date',
            'real_progress' => 'nullable|numeric|min:0|max:100',
            'phase' => 'nullable|string',
            'description' => 'nullable|string',
            'description_incidence' => 'nullable|string',
            'reason_incidence' => 'nullable|string',
            'description_risk' => 'nullable|string',
            'state_risk' => 'nullable|string',
            'description_change_control' => 'nullable|string',
            'billing' => 'nullable|numeric|min:0|max:100',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);

        // Añadir usuario que crea el proyecto
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $project = Project::create($validated);

        // Asignar usuarios si se proporcionan
        if (isset($validated['users'])) {
            $project->users()->sync($validated['users']);
        }

        $project->load(['entity', 'businessLine', 'users', 'creator', 'modifier', 'milestones']);
        return new ProjectResource($project);
    }

    /**
     * Actualizar un proyecto existente
     *
     * @param Request $request
     * @param Project $project
     * @return ProjectResource
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:projects,code,' . $project->id,
            'entity_id' => 'sometimes|required|exists:entities,id',
            'business_line_id' => 'nullable|exists:business_lines,id',
            'category' => 'nullable|string',
            'validity' => 'nullable|string|in:Vigente,Sin Vigencia',
            'state' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_date_projected' => 'nullable|date|after_or_equal:start_date',
            'end_date_real' => 'nullable|date|after_or_equal:start_date',
            'real_progress' => 'nullable|numeric|min:0|max:100',
            'phase' => 'nullable|string',
            'description' => 'nullable|string',
            'description_incidence' => 'nullable|string',
            'reason_incidence' => 'nullable|string',
            'description_risk' => 'nullable|string',
            'state_risk' => 'nullable|string',
            'description_change_control' => 'nullable|string',
            'billing' => 'nullable|numeric|min:0|max:100',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);

        // Actualizar usuario que modifica el proyecto
        $validated['updated_by'] = auth()->id();

        $project->update($validated);

        // Actualizar usuarios si se proporcionan
        if (isset($validated['users'])) {
            $project->users()->sync($validated['users']);
        }

        $project->load(['entity', 'businessLine', 'users', 'creator', 'modifier', 'milestones']);
        return new ProjectResource($project);
    }

    /**
     * Eliminar un proyecto
     *
     * @param Project $project
     * @return Response
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return response()->noContent();
    }

    /**
     * Obtener estadísticas de proyectos
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('state', 'Activo')->count();
        $completedProjects = Project::where('state', 'Completado')->count();
        $suspendedProjects = Project::where('state', 'Suspendido')->count();

        $projectsByCategory = Project::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->get();

        $projectsByPhase = Project::selectRaw('phase, count(*) as count')
            ->groupBy('phase')
            ->get();

        return response()->json([
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'suspended_projects' => $suspendedProjects,
            'projects_by_category' => $projectsByCategory,
            'projects_by_phase' => $projectsByPhase,
        ]);
    }
}

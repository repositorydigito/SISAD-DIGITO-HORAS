<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class UserTimeEntryController extends Controller
{
    /**
     * Obtener usuarios con sus horas registradas
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Añadir el último registro de tiempo
        $query->addSelect([
            'last_time_entry' => \App\Models\TimeEntry::select('created_at')
                ->whereColumn('user_id', 'users.id')
                ->latest()
                ->limit(1)
        ]);
        
        // Filtrar por fechas si se proporcionan
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Siempre agregar horas totales en el rango (todos los proyectos)
            $query->withSum(['timeEntries as total_hours_in_range' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }], 'hours');

            // Filtrar por proyecto si se proporciona
            if ($request->has('project_id')) {
                $projectId = $request->input('project_id');

                // Añadir información de horas en el proyecto específico y rango de fechas
                $query->withSum(['timeEntries' => function ($query) use ($startDate, $endDate, $projectId) {
                    $query->where('project_id', $projectId)
                          ->whereBetween('date', [$startDate, $endDate]);
                }], 'hours');

                // Solo incluir usuarios que tienen horas en este proyecto
                $query->whereHas('timeEntries', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                });
            }
        } else {
            // Filtrar por proyecto si se proporciona
            if ($request->has('project_id')) {
                $projectId = $request->input('project_id');

                // Añadir información de horas en el proyecto específico
                $query->withSum(['timeEntries' => function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                }], 'hours');

                // Solo incluir usuarios que tienen horas en este proyecto
                $query->whereHas('timeEntries', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                });
            } else {
                // Añadir información de horas totales (todos los proyectos)
                $query->withSum('timeEntries', 'hours');
            }
        }
        
        // Cargar proyectos relacionados
        $query->with('projects');
        
        // Ordenar por nombre
        $query->orderBy('name');
        
        $users = $query->get();

        // Debug temporal: verificar qué atributos se están generando
        if ($request->get('debug') == '1') {
            $firstUser = $users->first();
            return response()->json([
                'debug' => true,
                'request_params' => $request->all(),
                'users_count' => $users->count(),
                'first_user_attributes' => $firstUser ? array_keys($firstUser->getAttributes()) : [],
                'first_user_data' => $firstUser ? $firstUser->getAttributes() : null,
                'time_entries_sum_hours' => $firstUser ? $firstUser->time_entries_sum_hours : 'NOT_FOUND',
                'total_hours_in_range_sum_hours' => $firstUser ? $firstUser->total_hours_in_range_sum_hours : 'NOT_FOUND',
            ]);
        }

        return UserResource::collection($users);
    }
    
    /**
     * Obtener estadísticas de horas por usuario
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        // Filtrar por fechas si se proporcionan
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            $statistics = DB::table('time_entries')
                ->join('users', 'time_entries.user_id', '=', 'users.id')
                ->join('projects', 'time_entries.project_id', '=', 'projects.id')
                ->whereBetween('time_entries.date', [$startDate, $endDate])
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    DB::raw('SUM(time_entries.hours) as total_hours'),
                    DB::raw('COUNT(DISTINCT time_entries.project_id) as projects_count')
                )
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_hours', 'desc')
                ->get();
        } else {
            $statistics = DB::table('time_entries')
                ->join('users', 'time_entries.user_id', '=', 'users.id')
                ->join('projects', 'time_entries.project_id', '=', 'projects.id')
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    DB::raw('SUM(time_entries.hours) as total_hours'),
                    DB::raw('COUNT(DISTINCT time_entries.project_id) as projects_count')
                )
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_hours', 'desc')
                ->get();
        }
        
        return response()->json([
            'data' => $statistics
        ]);
    }
}

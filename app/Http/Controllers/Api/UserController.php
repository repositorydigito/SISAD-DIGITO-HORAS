<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * Obtener listado de todos los usuarios del sistema
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Cargar roles para cada usuario
        $query->with('roles');

        // Filtros opcionales
        if ($request->has('active')) {
            // Filtrar por usuarios activos (que tienen email verificado o no tienen fecha de verificación)
            if ($request->boolean('active')) {
                $query->where(function ($q) {
                    $q->whereNotNull('email_verified_at')
                      ->orWhereNull('email_verified_at');
                });
            } else {
                // Para usuarios inactivos, podrías implementar lógica específica
                // Por ahora, mantenemos todos los usuarios
            }
        }

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortField = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        
        // Validar campos de ordenamiento permitidos
        $allowedSortFields = ['id', 'name', 'email', 'created_at', 'updated_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'name';
        }
        
        $query->orderBy($sortField, $sortDirection);

        // Paginación
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * Obtener un usuario específico
     *
     * @param User $user
     * @return UserResource
     */
    public function show(User $user)
    {
        // Cargar relaciones
        $user->load(['roles', 'projects']);

        return new UserResource($user);
    }

    /**
     * Obtener estadísticas de usuarios
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('email_verified_at')->count();
        
        // Estadísticas por roles
        $usersByRole = User::with('roles')
            ->get()
            ->groupBy(function ($user) {
                return $user->roles->first()->name ?? 'Sin Rol';
            })
            ->map(function ($users) {
                return $users->count();
            });

        // Usuarios con proyectos asignados
        $usersWithProjects = User::whereHas('projects')->count();

        // Usuarios con registros de tiempo
        $usersWithTimeEntries = User::whereHas('timeEntries')->count();

        return response()->json([
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'users_with_projects' => $usersWithProjects,
            'users_with_time_entries' => $usersWithTimeEntries,
            'users_by_role' => $usersByRole,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntityController extends Controller
{
    /**
     * Obtener listado de todas las entidades del sistema
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Entity::query();

        // Cargar relaciones
        $query->with(['bank', 'creator', 'modifier']);

        // Filtros
        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->has('business_group')) {
            $query->where('business_group', 'like', '%' . $request->business_group . '%');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('trade_name', 'like', "%{$search}%")
                  ->orWhere('tax_id', 'like', "%{$search}%")
                  ->orWhere('business_group', 'like', "%{$search}%");
            });
        }

        // Filtro por RUC específico
        if ($request->has('tax_id')) {
            $query->where('tax_id', 'like', '%' . $request->tax_id . '%');
        }

        // Ordenamiento
        $sortField = $request->input('sort_field', 'business_name');
        $sortDirection = $request->input('sort_direction', 'asc');
        
        // Validar campos de ordenamiento permitidos
        $allowedSortFields = ['id', 'entity_type', 'business_name', 'trade_name', 'tax_id', 'business_group', 'created_at', 'updated_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'business_name';
        }
        
        $query->orderBy($sortField, $sortDirection);

        // Paginación
        $perPage = $request->input('per_page', 15);
        $entities = $query->paginate($perPage);

        return EntityResource::collection($entities);
    }

    /**
     * Obtener una entidad específica
     *
     * @param Entity $entity
     * @return EntityResource
     */
    public function show(Entity $entity)
    {
        // Cargar relaciones
        $entity->load(['bank', 'creator', 'modifier', 'projects']);

        return new EntityResource($entity);
    }

    /**
     * Obtener estadísticas de entidades
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $totalEntities = Entity::count();
        
        // Estadísticas por tipo de entidad
        $entitiesByType = Entity::selectRaw('entity_type, count(*) as count')
            ->groupBy('entity_type')
            ->get()
            ->pluck('count', 'entity_type');

        // Estadísticas por grupo empresarial
        $entitiesByBusinessGroup = Entity::whereNotNull('business_group')
            ->where('business_group', '!=', '')
            ->selectRaw('business_group, count(*) as count')
            ->groupBy('business_group')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->pluck('count', 'business_group');

        // Entidades con proyectos
        $entitiesWithProjects = Entity::whereHas('projects')->count();

        // Entidades con ingresos
        $entitiesWithIncomes = Entity::whereHas('incomes')->count();

        // Entidades con gastos
        $entitiesWithExpenses = Entity::whereHas('expenses')->count();

        // Entidades con información bancaria
        $entitiesWithBankInfo = Entity::whereNotNull('bank_id')->count();

        return response()->json([
            'total_entities' => $totalEntities,
            'entities_with_projects' => $entitiesWithProjects,
            'entities_with_incomes' => $entitiesWithIncomes,
            'entities_with_expenses' => $entitiesWithExpenses,
            'entities_with_bank_info' => $entitiesWithBankInfo,
            'entities_by_type' => $entitiesByType,
            'entities_by_business_group' => $entitiesByBusinessGroup,
        ]);
    }

    /**
     * Obtener tipos de entidad disponibles
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function types()
    {
        // Obtener los tipos de entidad únicos de la base de datos
        $types = Entity::distinct('entity_type')
            ->whereNotNull('entity_type')
            ->pluck('entity_type')
            ->sort()
            ->values();

        return response()->json([
            'data' => $types
        ]);
    }

    /**
     * Obtener grupos empresariales disponibles
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function businessGroups()
    {
        // Obtener los grupos empresariales únicos de la base de datos
        $businessGroups = Entity::distinct('business_group')
            ->whereNotNull('business_group')
            ->where('business_group', '!=', '')
            ->pluck('business_group')
            ->sort()
            ->values();

        return response()->json([
            'data' => $businessGroups
        ]);
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->isActive(),
            'role' => $this->getPrimaryRole(),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),
            'email_verified_at' => $this->email_verified_at,
            'avatar_url' => $this->avatar_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Información de tiempo (solo cuando se carga desde contexto de tiempo)
            'total_hours' => $this->when(isset($this->total_hours), function () {
                return (float) $this->total_hours;
            }, function () {
                return $this->when(isset($this->time_entries_sum_hours) || isset($this->total_hours_in_range_sum_hours), function () {
                    return (float) ($this->time_entries_sum_hours ?? $this->total_hours_in_range_sum_hours ?? 0);
                });
            }),
            'total_hours_in_range' => $this->when(isset($this->total_hours_in_range_sum_hours), function () {
                return (float) $this->total_hours_in_range_sum_hours;
            }),
            'total_hours_in_project' => $this->when(isset($this->time_entries_sum_hours), function () {
                return (float) $this->time_entries_sum_hours;
            }),
            'projects_count' => $this->when($this->relationLoaded('projects'), function () {
                return $this->projects->count();
            }, function () {
                return $this->when(request()->routeIs('api.users.*'), function () {
                    return $this->projects()->count();
                });
            }),
            'last_time_entry' => $this->when(isset($this->last_time_entry), function () {
                return [
                    'date' => $this->last_time_entry ? Carbon::parse($this->last_time_entry)->format('Y-m-d H:i:s') : null,
                    'human_diff' => $this->last_time_entry ? Carbon::parse($this->last_time_entry)->diffForHumans() : null,
                    'days_since_last_entry' => $this->last_time_entry ? Carbon::parse($this->last_time_entry)->diffInDays(now()) : null,
                ];
            }),
        ];
    }

    /**
     * Determinar si el usuario está activo
     */
    private function isActive(): bool
    {
        // Un usuario se considera activo si:
        // 1. Tiene email verificado, O
        // 2. Fue creado recientemente (últimos 30 días) y no tiene verificación requerida
        return $this->email_verified_at !== null || 
               ($this->email_verified_at === null && $this->created_at->gt(now()->subDays(30)));
    }

    /**
     * Obtener el rol principal del usuario
     */
    private function getPrimaryRole(): ?string
    {
        if ($this->relationLoaded('roles') && $this->roles->isNotEmpty()) {
            return $this->roles->first()->name;
        }
        
        // Si no están cargados los roles, intentar obtener el primero
        $firstRole = $this->roles()->first();
        return $firstRole ? $firstRole->name : null;
    }
}

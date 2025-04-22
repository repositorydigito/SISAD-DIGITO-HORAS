<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'code' => $this->code,
            'entity' => [
                'id' => $this->entity->id ?? null,
                'name' => $this->entity->business_name ?? null,
            ],
            'business_line' => [
                'id' => $this->businessLine->id ?? null,
                'name' => $this->businessLine->name ?? null,
            ],
            'category' => $this->category,
            'state' => $this->state,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'end_date_projected' => $this->end_date_projected,
            'end_date_real' => $this->end_date_real,
            'real_progress' => $this->real_progress,
            'phase' => $this->phase,
            'description' => $this->description,
            'description_incidence' => $this->description_incidence,
            'reason_incidence' => $this->reason_incidence,
            'description_risk' => $this->description_risk,
            'state_risk' => $this->state_risk,
            'description_change_control' => $this->description_change_control,
            'billing' => $this->billing,
            'delay_days' => $this->delay_days ?? $this->calculateDelayDays(),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? null,
            ],
            'updated_by' => [
                'id' => $this->modifier->id ?? null,
                'name' => $this->modifier->name ?? null,
            ],
            // Campos calculados
            'planned_progress' => $this->calculatePlannedProgress(),
            'pending_billing' => $this->calculatePendingBilling(),
        ];
    }

    /**
     * Calcular el progreso planificado
     */
    private function calculatePlannedProgress(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $startDate = \Carbon\Carbon::parse($this->start_date);
        $endDate = \Carbon\Carbon::parse($this->end_date);
        $today = \Carbon\Carbon::now();

        // Si la fecha actual es anterior a la fecha de inicio, el progreso es 0%
        if ($today->lt($startDate)) {
            return 0;
        }

        // Si la fecha actual es posterior a la fecha de finalización, el progreso es 100%
        if ($today->gt($endDate)) {
            return 100;
        }

        // Calcular el progreso planificado
        $totalDays = $startDate->diffInDays($endDate) ?: 1; // Evitar división por cero
        $daysElapsed = $startDate->diffInDays($today);
        $plannedProgress = ($daysElapsed / $totalDays) * 100;

        return round($plannedProgress, 2);
    }

    /**
     * Calcular la facturación pendiente
     */
    private function calculatePendingBilling(): float
    {
        if (!isset($this->billing)) {
            return 100;
        }

        $pendingBilling = 100 - $this->billing;
        return max(0, round($pendingBilling, 2));
    }
}

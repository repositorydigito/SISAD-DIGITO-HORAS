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
            'validity' => $this->validity,
            'state' => $this->state,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'end_date_projected' => $this->end_date_projected,
            'end_date_real' => $this->end_date_real,
            'real_progress' => $this->real_progress ? $this->real_progress / 100 : 0,
            'phase' => $this->phase,
            'description' => $this->description,
            'description_incidence' => $this->description_incidence,
            'reason_incidence' => $this->reason_incidence,
            'description_risk' => $this->description_risk,
            'state_risk' => $this->state_risk,
            'description_change_control' => $this->description_change_control,
            'billing' => $this->billing ? $this->billing / 100 : 0,
            'delay_days' => $this->delay_days ?? $this->calculateDelayDays(),
            'total_hours' => $this->when(isset($this->total_hours), function () {
                return (float) $this->total_hours;
            }, function () {
                return (float) \App\Models\TimeEntry::where('project_id', $this->id)->sum('hours');
            }),
            'total_hours_in_range' => $this->when(isset($this->total_hours_in_range), function () {
                return (float) $this->total_hours_in_range;
            }),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'milestones' => $this->whenLoaded('milestones', function () {
                return $this->milestones->map(function ($milestone) {
                    // Usar el atributo calculado para las horas totales
                    $totalHours = $milestone->total_hours;

                    return [
                        'id' => $milestone->id,
                        'name' => $milestone->name,
                        'description' => $milestone->description,
                        'start_date' => $milestone->start_date,
                        'end_date' => $milestone->end_date,
                        'billing_percentage' => $milestone->billing_percentage / 100,
                        'status' => $milestone->status,
                        'is_paid' => (bool) $milestone->is_paid,
                        'order' => $milestone->order,
                        'total_hours' => (float) $totalHours,
                    ];
                });
            }),
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
     * Calcular el progreso planificado (en formato decimal 0-1)
     */
    private function calculatePlannedProgress(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $startDate = \Carbon\Carbon::parse($this->start_date);
        $endDate = \Carbon\Carbon::parse($this->end_date);
        $today = \Carbon\Carbon::now();

        // Si la fecha actual es anterior a la fecha de inicio, el progreso es 0
        if ($today->lt($startDate)) {
            return 0;
        }

        // Si la fecha actual es posterior a la fecha de finalización, el progreso es 1
        if ($today->gt($endDate)) {
            return 1;
        }

        // Calcular el progreso planificado
        $totalDays = $startDate->diffInDays($endDate) ?: 1; // Evitar división por cero
        $daysElapsed = $startDate->diffInDays($today);
        $plannedProgress = $daysElapsed / $totalDays;

        return round($plannedProgress, 2);
    }

    /**
     * Calcular la facturación pendiente (en formato decimal 0-1)
     */
    private function calculatePendingBilling(): float
    {
        if (!isset($this->billing)) {
            return 1;
        }

        $pendingBilling = 1 - ($this->billing / 100);
        return max(0, round($pendingBilling, 2));
    }
}

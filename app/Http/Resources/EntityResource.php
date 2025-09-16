<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityResource extends JsonResource
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
            'entity_type' => $this->entity_type,
            'business_name' => $this->business_name,
            'tax_id' => $this->tax_id,
            'trade_name' => $this->trade_name,
            'business_group' => $this->business_group,
            
            // Información adicional
            'billing_email' => $this->billing_email,
            'copy_email' => $this->copy_email,
            'credit_days' => $this->credit_days,
            'reference_recommendation' => $this->reference_recommendation,
            
            // Información bancaria
            'bank' => $this->whenLoaded('bank', function () {
                return [
                    'id' => $this->bank->id,
                    'name' => $this->bank->name,
                ];
            }),
            'account_number' => $this->account_number,
            'interbank_account_number' => $this->interbank_account_number,
            'detraccion_account_number' => $this->detraccion_account_number,
            
            // Contadores de relaciones
            'projects_count' => $this->when($this->relationLoaded('projects'), function () {
                return $this->projects->count();
            }, function () {
                return $this->when(request()->routeIs('api.entities.*'), function () {
                    return $this->projects()->count();
                });
            }),
            'incomes_count' => $this->when(request()->routeIs('api.entities.*'), function () {
                return $this->incomes()->count();
            }),
            'expenses_count' => $this->when(request()->routeIs('api.entities.*'), function () {
                return $this->expenses()->count();
            }),
            
            // Fechas y auditoría
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'updated_by' => $this->whenLoaded('modifier', function () {
                return [
                    'id' => $this->modifier->id,
                    'name' => $this->modifier->name,
                ];
            }),
            
            // Campos calculados
            'entity_type_label' => $this->getEntityTypeLabel(),
            'has_complete_info' => $this->hasCompleteInfo(),
        ];
    }

    /**
     * Obtener la etiqueta legible del tipo de entidad
     */
    private function getEntityTypeLabel(): string
    {
        $labels = [
            'Client' => 'Cliente',
            'Cliente' => 'Cliente',
            'Supplier' => 'Proveedor',
            'Proveedor' => 'Proveedor',
            'Payroll' => 'Planilla',
            'Planilla' => 'Planilla',
        ];

        return $labels[$this->entity_type] ?? $this->entity_type;
    }

    /**
     * Verificar si la entidad tiene información completa
     */
    private function hasCompleteInfo(): bool
    {
        $requiredFields = [
            'entity_type',
            'business_name',
            'tax_id',
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }
}
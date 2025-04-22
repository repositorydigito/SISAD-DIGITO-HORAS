<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    protected $fillable = [
        'entity_id', 'project_id', 'document_type', 'document_number', 'document_date',
        'description', 'currency', 'amount_usd', 'amount_pen', 'payment_plan_date',
        'real_payment_date', 'status', 'service_percentage', 'deposit_amount',
        'detraccion_amount', 'is_accounted', 'observations', 'attachment_path',
    ];

    /**
     * Relación con el modelo Entity.
     * Un ingreso pertenece a una entidad.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Relación con el modelo Project.
     * Un ingreso pertenece a un proyecto.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

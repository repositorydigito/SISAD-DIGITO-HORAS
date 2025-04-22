<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentLog extends Model
{
    protected $fillable = [
        'project_id',
        'equipment_id',
        'date',
        'diesel_gal',
        'start_time',
        'end_time',
        'engine_hours',
        'delay_hours',
        'delay_activity',
        'initial_mileage',
        'final_mileage',
        'tons'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function equipment():belongsTo
    {
    return $this->belongsTo(Equipment::class);
    }


     // Accessor para calcular kilometraje total
     public function getTotalMileageAttribute(): float
     {
         if ($this->final_mileage && $this->initial_mileage) {
             return $this->final_mileage - $this->initial_mileage;
         }
         return 0;
     }

}

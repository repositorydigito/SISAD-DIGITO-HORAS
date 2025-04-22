<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{

    protected $table = 'equipments';
    protected $fillable = [
        'name',
        'vehicle_type',
        'entity_id',
        'driver',
        'license',
        'plate_number1',
        'plate_number2',
        'brand',
        'model'
    ];

   
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }


    public function equipmentLogs(): HasMany
    {
        return $this->hasMany(EquipmentLog::class);
    }

 
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function projects(): HasMany 
    {
        return $this->hasMany(Project::class);
    }


    public function salesTargets()
    {
        return $this->hasMany(SalesTarget::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'planned_date',
        'real_date',
        'progress',
        'amount',
        'status',
        'comments',
        'order',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TimeEntry;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'billing_percentage',
        'status',
        'progress',
        'is_paid',
        'order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'billing_percentage' => 'decimal:2',
        'progress' => 'decimal:2',
        'is_paid' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Obtener el proyecto al que pertenece este hito
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Obtener las entradas de tiempo asociadas a este hito
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'milestone_id');
    }

    /**
     * Calcular el total de horas registradas para este hito
     */
    public function getTotalHoursAttribute(): float
    {
        // También podemos calcular las horas basadas en las fechas del hito
        $startDate = \Carbon\Carbon::parse($this->start_date)->startOfDay();
        $endDate = \Carbon\Carbon::parse($this->end_date)->endOfDay();

        return (float) TimeEntry::where('project_id', $this->project_id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                      ->orWhere('milestone_id', $this->id); // También incluir entradas explícitamente asignadas a este hito
            })
            ->sum('hours');
    }
}

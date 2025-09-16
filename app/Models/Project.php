<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'code',
        'entity_id',
        'business_line_id',
        'category',
        'validity',
        'state',
        'start_date',
        'end_date',
        'end_date_projected',
        'end_date_real',
        'real_progress',
        'phase',
        'description',
        'description_incidence',
        'reason_incidence',
        'description_risk',
        'state_risk',
        'description_change_control',
        'billing',
        'delay_days',
        'created_by',
        'updated_by',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function equipmentLogs(): HasMany
    {
        return $this->hasMany(EquipmentLog::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function businessLine(): BelongsTo
    {
        return $this->belongsTo(BusinessLine::class);
    }
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Obtener los hitos del proyecto
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('order');
    }

    /**
     * Obtener los hitos de facturación del proyecto
     */
    public function billingMilestones(): HasMany
    {
        return $this->hasMany(BillingMilestone::class)->orderBy('order');
    }

    /**
     * Calcular los días de desfase entre la fecha de finalización planificada y la fecha de finalización proyectada
     *
     * @return int
     */
    public function calculateDelayDays(): int
    {
        if (!$this->end_date || !$this->end_date_projected) {
            return 0;
        }

        $endDateObj = \Carbon\Carbon::parse($this->end_date);
        $endDateProjectedObj = \Carbon\Carbon::parse($this->end_date_projected);

        // Si la fecha proyectada es anterior a la fecha planificada, no hay desfase
        if ($endDateProjectedObj->lte($endDateObj)) {
            return 0;
        }

        // Calcular días laborables entre las dos fechas
        $delayDays = 0;
        $currentDate = clone $endDateObj;

        while ($currentDate->lt($endDateProjectedObj)) {
            // Si no es sábado (6) ni domingo (0)
            if (!in_array($currentDate->dayOfWeek, [0, 6])) {
                $delayDays++;
            }
            $currentDate->addDay();
        }

        return $delayDays;
    }
}

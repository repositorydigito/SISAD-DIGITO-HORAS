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
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Calcular los días de desfase entre la fecha de finalización y la fecha de finalización real
     *
     * @return int
     */
    public function calculateDelayDays(): int
    {
        if (!$this->end_date || !$this->end_date_real) {
            return 0;
        }

        $endDateObj = \Carbon\Carbon::parse($this->end_date);
        $endDateRealObj = \Carbon\Carbon::parse($this->end_date_real);

        // Si la fecha real es anterior a la fecha proyectada, no hay desfase
        if ($endDateRealObj->lte($endDateObj)) {
            return 0;
        }

        // Calcular días laborables entre las dos fechas
        $delayDays = 0;
        $currentDate = clone $endDateObj;

        while ($currentDate->lt($endDateRealObj)) {
            // Si no es sábado (6) ni domingo (0)
            if (!in_array($currentDate->dayOfWeek, [0, 6])) {
                $delayDays++;
            }
            $currentDate->addDay();
        }

        return $delayDays;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;


class TimeEntryReport extends Model
{
    protected $table = 'time_entries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'date',
        'phase',
        'hours'
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2'
    ];

    public const PHASES = [
        'inicio' => 'Inicio',
        'planificacion' => 'Planificación',
        'ejecucion' => 'Ejecución',
        'control' => 'Control',
        'cierre' => 'Cierre'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }


}

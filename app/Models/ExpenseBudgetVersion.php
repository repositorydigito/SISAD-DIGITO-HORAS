<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseBudgetVersion extends Model
{
    protected $fillable = [
        'version_number',
        'status',
        'year',
        'created_by',
        'approved_at',
        'approved_by',
        'comments'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function expenseBudgets(): HasMany
    {
        return $this->hasMany(ExpenseBudget::class, 'version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $maxVersion = static::where('year', $model->year)
                ->max('version_number') ?? 0;

            $model->version_number = $maxVersion + 1;
            $model->created_by = auth()->id();
            $model->status = 'draft';
        });
    }
}

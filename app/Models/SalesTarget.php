<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTarget extends Model
{
    protected $fillable = [
        'version_id',
        'business_line_id',
        'january_amount',
        'february_amount',
        'march_amount',
        'april_amount',
        'may_amount',
        'june_amount',
        'july_amount',
        'august_amount',
        'september_amount',
        'october_amount',
        'november_amount',
        'december_amount',
        'created_by'
    ];

    protected $casts = [
        'january_amount' => 'decimal:2',
        'february_amount' => 'decimal:2',
        'march_amount' => 'decimal:2',
        'april_amount' => 'decimal:2',
        'may_amount' => 'decimal:2',
        'june_amount' => 'decimal:2',
        'july_amount' => 'decimal:2',
        'august_amount' => 'decimal:2',
        'september_amount' => 'decimal:2',
        'october_amount' => 'decimal:2',
        'november_amount' => 'decimal:2',
        'december_amount' => 'decimal:2',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(SalesTargetVersion::class, 'version_id');
    }

    public function businessLine(): BelongsTo
    {
        return $this->belongsTo(BusinessLine::class, 'business_line_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


}
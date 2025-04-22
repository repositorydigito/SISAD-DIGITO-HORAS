<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesTargetVersion extends Model
{
   protected $fillable = [
       'year',
       'version_number',
       'status',
       'created_by',
       'approved_at',
       'approved_by',
       'comments'
   ];

   protected $casts = [
       'approved_at' => 'datetime',
       'version_number' => 'integer',
       'year' => 'integer',
       'status' => 'string'
   ];

   public function salesTargets(): HasMany
   {
       return $this->hasMany(SalesTarget::class, 'version_id');
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

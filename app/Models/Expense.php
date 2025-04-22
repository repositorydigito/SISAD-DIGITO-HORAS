<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'entity_id',
        'document_type',
        'document_number',
        'document_date',
        'cost_center_id',
        'category_id',
        'remark',
        'currency',
        'amount_usd',
        'amount_pen',
        'exchange_rate',
        'withholding_amount',
        'status',
        'payment_status',
        'project_id',
        'planned_payment_date',
        'actual_payment_date',
        'expense_type',
        'amount_to_pay',
        'responsible_id',
        'has_attachment',
        'observations',
        'accounting',
        'created_by',
        'updated_by',
        'attachment_path',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

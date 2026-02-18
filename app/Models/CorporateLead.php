<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateLead extends Model
{
    protected $fillable = [
        'company_name',
        'pic_name',
        'pic_phone',
        'address',
        'potential_pax',
        'budget_estimation',
        'sales_id',
        'status', // prospecting, presentation, negotiation, deal, lost
        'notes',
    ];

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_id');
    }
}
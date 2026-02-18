<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'city',
        'source',
        'agent_id',
        'sales_id',
        'potential_package', 
        'status',            // cold, warm, hot, closing, lost, converted
        'notes',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_id');
    }

    
}
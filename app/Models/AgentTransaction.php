<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTransaction extends Model
{
    protected $fillable = [
        'agent_wallet_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'proof_file',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(AgentWallet::class, 'agent_wallet_id');
    }
}
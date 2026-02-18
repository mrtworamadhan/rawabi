<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentCheck extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'ktp' => 'boolean',
        'kk' => 'boolean',
        'akta' => 'boolean',  
        'buku_nikah' => 'boolean',
        'visa_issue_date' => 'date',
        'visa_expiry_date' => 'date',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
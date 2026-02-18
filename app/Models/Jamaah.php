<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jamaah extends Model
{
    use HasFactory;

    protected $table = 'jamaahs';
    protected $guarded = ['id'];

    protected $casts = [
        'nik' => 'string',
        'passport_expiry' => 'date',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
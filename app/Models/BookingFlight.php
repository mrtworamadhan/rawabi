<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingFlight extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function booking(): BelongsTo 
    { 
        return $this->belongsTo(Booking::class); 
    }
    public function packageFlight(): BelongsTo 
    { 
        return $this->belongsTo(PackageFlight::class); 
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function jamaah(): BelongsTo
    {
        return $this->belongsTo(Jamaah::class);
    }

    public function umrahPackage(): BelongsTo 
    {
        return $this->belongsTo(UmrahPackage::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function documentCheck(): HasOne
    {
        return $this->hasOne(DocumentCheck::class);
    }

    public function roomAssignment(): HasOne
    {
        return $this->hasOne(RoomAssignment::class);
    }
    
    public function getRoomNumberAttribute()
    {
        return $this->roomAssignment->room_number ?? '-';
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function bookingFlights()
    {
        return $this->hasMany(BookingFlight::class);
    }
}
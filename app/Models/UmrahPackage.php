<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class UmrahPackage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'price' => 'decimal:2',
    ];

    public function flights(): HasMany
    {
        return $this->hasMany(PackageFlight::class);
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(PackageHotel::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function jamaahs(): HasManyThrough
    {
        return $this->hasManyThrough(Jamaah::class, Booking::class, 'umrah_package_id', 'id', 'id', 'jamaah_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getTotalIncomeAttribute()
    {
        return $this->bookings->sum(function ($booking) {
            return $booking->payments()
                ->whereNotNull('verified_at') 
                ->sum('amount');
        });
    }

    public function getPotentialRevenueAttribute()
    {
        return $this->bookings()
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');
    }

    public function getTotalReceivableAttribute()
    {
        return $this->getPotentialRevenueAttribute() - $this->getTotalIncomeAttribute();
    }

    public function rundowns()
    {
        return $this->hasMany(Rundown::class);
    }
}
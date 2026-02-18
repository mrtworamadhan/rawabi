<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rundown extends Model
{
    use HasFactory;

    protected $fillable = [
        'umrah_package_id',
        'phase',
        'date',
        'day_number',
        'time_start',
        'time_end',
        'activity',
        'location',
        'description',
        'pic_name' 
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function package()
    {
        return $this->belongsTo(UmrahPackage::class, 'umrah_package_id');
    }
}
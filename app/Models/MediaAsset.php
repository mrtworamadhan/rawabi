<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'tags' => 'array',
    ];

    public function umrahPackage()
    {
        return $this->belongsTo(UmrahPackage::class);
    }
}
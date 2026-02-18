<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
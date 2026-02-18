<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeWallet extends Model
{
    protected $fillable = [
        'name',
        'balance',
        'type',
        'code', 
    ];

    public static function cashierBox()
    {
        return self::firstOrCreate(
            ['code' => 'CASHIER-01'],
            [
                'name' => 'Laci Kasir Utama',
                'type' => 'cashier',
                'balance' => 0
            ]
        );
    }

    public static function pettyCash()
    {
        return self::firstOrCreate(
            ['code' => 'PETTY-01'],
            [
                'name' => 'Kas Kecil Operasional',
                'type' => 'petty_cash',
                'balance' => 0
            ]
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'office_wallet_id');
    }
}
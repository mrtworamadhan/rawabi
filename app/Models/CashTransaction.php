<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CashTransaction extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function wallet()
    {
        return $this->belongsTo(OfficeWallet::class, 'office_wallet_id');
    }

    protected static function booted()
    {
        static::created(function ($transaction) {
            $wallet = $transaction->wallet;
            
            if ($transaction->type === 'deposit') {
                $wallet->increment('balance', $transaction->amount);
            } else {
                $wallet->decrement('balance', $transaction->amount);
            }
        });
        
        // Handle jika transaksi dihapus (Rollback saldo)
        static::deleted(function ($transaction) {
            $wallet = $transaction->wallet;
            
            if ($transaction->type === 'deposit') {
                $wallet->decrement('balance', $transaction->amount);
            } else {
                $wallet->increment('balance', $transaction->amount);
            }
        });
    }
}
<?php

namespace App\Filament\Resources\CashTransactions\Pages;

use App\Filament\Resources\CashTransactions\CashTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCashTransaction extends CreateRecord
{
    protected static string $resource = CashTransactionResource::class;
}

<?php

namespace App\Filament\Resources\CashTransactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CashTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('office_wallet_id')
                    ->required()
                    ->numeric(),
                Select::make('type')
                    ->options(['deposit' => 'Deposit', 'withdrawal' => 'Withdrawal'])
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                DatePicker::make('transaction_date')
                    ->required(),
                TextInput::make('description')
                    ->required(),
                TextInput::make('expense_id')
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}

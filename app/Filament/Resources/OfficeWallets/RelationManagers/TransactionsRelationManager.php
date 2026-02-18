<?php

namespace App\Filament\Resources\OfficeWallets\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('transaction_date')
                    ->date(),
                TextEntry::make('description'),
                TextEntry::make('expense_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_date')
            ->columns([
                TextColumn::make('transaction_date')
                    ->date('d M Y')
                    ->label('Tanggal'),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'deposit' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'deposit' ? 'Masuk' : 'Keluar'),

                TextColumn::make('amount')
                    ->money('IDR')
                    ->color(fn ($record) => $record->type === 'deposit' ? 'success' : 'danger'),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}

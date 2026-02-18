<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('name')
                    ->searchable()
                    ->limit(30),
                
                TextColumn::make('wallet.name')
                    ->badge()
                    ->color('info'),

                TextColumn::make('category.name')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),

            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                SelectFilter::make('expense_category_id')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
                
                SelectFilter::make('office_wallet_id')
                    ->relationship('wallet', 'name')
                    ->label('Sumber Kas'),
            ])
            ->recordActions([
                EditAction::make()->label(''),
                DeleteAction::make()->label('')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

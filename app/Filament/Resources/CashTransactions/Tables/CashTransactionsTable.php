<?php

namespace App\Filament\Resources\CashTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y H:i')
                    ->sortable(),

                TextColumn::make('wallet.name')
                    ->label('Sumber Kas')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('type')
                    ->label('Arus Kas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdrawal' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'deposit' => 'Uang Masuk',
                        'withdrawal' => 'Uang Keluar',
                    }),

                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->weight('bold')
                    ->color(fn ($record) => $record->type === 'deposit' ? 'success' : 'danger')
                    ->prefix(fn ($record) => $record->type === 'deposit' ? '+ ' : '- '),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('expense.category.name')
                    ->label('Kategori Expense')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                SelectFilter::make('office_wallet_id')
                    ->label('Filter Dompet')
                    ->relationship('wallet', 'name'),
                
                SelectFilter::make('type')
                    ->options([
                        'deposit' => 'Uang Masuk',
                        'withdrawal' => 'Uang Keluar',
                    ]),
                    
                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('transaction_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('transaction_date', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

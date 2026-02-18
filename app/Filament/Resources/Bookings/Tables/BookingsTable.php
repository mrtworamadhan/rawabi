<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')->searchable()->label('Kode'),
            
                TextColumn::make('created_at')->date('d M Y')->label('Tgl Booking'),
                
                TextColumn::make('jamaah.name')
                    ->label('Nama Jamaah')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('umrahPackage.name')
                    ->label('Paket')
                    ->limit(20),

                TextColumn::make('total_price')
                    ->money('IDR')
                    ->label('Total Tagihan'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'booking' => 'gray',
                        'dp_paid' => 'warning',
                        'paid_in_full' => 'success',
                        'cancelled' => 'danger',
                        'reschedule' => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('status'),
                SelectFilter::make('umrah_package_id')
                    ->relationship('umrahPackage', 'name')
                    ->label('Filter Paket'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

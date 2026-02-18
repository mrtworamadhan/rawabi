<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBookings extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 3; 
    protected int | string | array $columnSpan = 'full'; 

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y'),
                
                TextColumn::make('jamaah.name')
                    ->label('Jamaah')
                    ->weight('bold'),

                TextColumn::make('umrahPackage.name')
                    ->label('Paket')
                    ->limit(20),

                TextColumn::make('total_price')
                    ->label('Nilai')
                    ->money('IDR'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'booking' => 'gray',
                        'dp_paid' => 'warning',
                        'paid_in_full' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
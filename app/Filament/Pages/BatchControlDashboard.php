<?php

namespace App\Filament\Pages;

use App\Models\UmrahPackage;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use BackedEnum;
use Filament\Tables\Table;
use UnitEnum;
use Filament\Support\Icons\Heroicon;



class BatchControlDashboard extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Batch';
    protected static ?string $title = 'Monitoring Batch Umrah';
    protected static ?int $navigationSort =1;
    protected string $view = 'filament.pages.batch-control-dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(UmrahPackage::query()->latest('departure_date')->with(['bookings.payments']))
            ->columns([
                TextColumn::make('name')
                    ->label('Paket Umrah')
                    ->description(fn ($record) => $record->departure_date->format('d M Y'))
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('bookings_count')
                    ->counts('bookings')
                    ->label('Okupansi Seat')
                    ->formatStateUsing(fn ($state, $record) => "{$state} / {$record->target_jamaah} Pax")
                    ->description(function ($record) {
                        $persen = $record->target_jamaah > 0 ? ($record->bookings_count / $record->target_jamaah) * 100 : 0;
                        return number_format($persen, 0) . '% Terisi';
                    })
                    ->badge()
                    ->color(fn ($state, $record) => $state >= $record->target_jamaah ? 'success' : 'warning'),

                TextColumn::make('status_bayar')
                    ->label('Status Pembayaran')
                    ->state(function (UmrahPackage $record) {
                        $lunas = $record->bookings->where('status', 'paid_in_full')->count();
                        
                        $belum = $record->bookings->count() - $lunas;

                        return "Lunas: {$lunas} | Belum: {$belum}";
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('financial_summary')
                    ->label('Keuangan (Terkumpul vs Piutang)')
                    ->state(fn (UmrahPackage $record) => 'Terkumpul: ' . number_format($record->total_income, 0, ',', '.')) 
                    ->description(fn (UmrahPackage $record) => 'Piutang: ' . number_format($record->total_receivable, 0, ',', '.'))
                    ->color('info')
                    ->weight('bold'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'open' => 'success',
                        'full' => 'warning',
                        'departed' => 'info',
                        'completed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('detail')
                    ->label('Lihat Laporan Detail')
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->url(fn (UmrahPackage $record) => BatchDetail::getUrl(['record' => $record->id])),
            ]);
    }
}

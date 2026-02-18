<?php

namespace App\Filament\Pages;

use App\Models\Payment;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use UnitEnum;


class FinancialReport extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?int $navigationSort =2;
    protected string $view = 'filament.pages.financial-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(Payment::query()->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y'),
                
                TextColumn::make('booking.booking_code')
                    ->label('Kode Booking')
                    ->searchable(),

                TextColumn::make('booking.jamaah.name')
                    ->label('Dari Jamaah')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipe') 
                    ->badge(),

                TextColumn::make('method')
                    ->label('Via'),

                TextColumn::make('amount')
                    ->label('Masuk (In)')
                    ->money('IDR')
                    ->summarize(Sum::make()->label('Total')),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('dari_tgl'),
                        DatePicker::make('sampai_tgl'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['dari_tgl'], fn ($q) => $q->whereDate('created_at', '>=', $data['dari_tgl']))
                            ->when($data['sampai_tgl'], fn ($q) => $q->whereDate('created_at', '<=', $data['sampai_tgl']));
                    })
            ])
            ->headerActions([
                // Tombol Export Excel
                // ExportAction::make()->exports([\pxlrbt\FilamentExcel\Exports\ExcelExport::make()]),
            ]);
    }
}

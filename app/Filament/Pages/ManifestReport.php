<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use BackedEnum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ManifestReport extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Manifest & Persiapan';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.manifest-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->where('status', '!=', 'cancelled')
            )
            ->columns([
                TextColumn::make('umrahPackage.name')
                    ->label('Batch / Paket')
                    ->badge()
                    ->color('info'),

                TextColumn::make('jamaah.name')
                    ->label('Nama Jamaah')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('jamaah.passport_number')
                    ->label('No. Paspor')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                
                IconColumn::make('documentCheck.passport_status')
                    ->label('Fisik Paspor')
                    ->boolean(),

                TextColumn::make('documentCheck.visa_status')
                    ->label('Status Visa')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'issued' => 'success',
                        'requested' => 'warning',
                        default => 'danger'
                    }),
                
                TextColumn::make('umrahPackage.hotels.hotel_name')
                    ->label('Info Hotel')
                    ->limit(20)
                    ->listWithLineBreaks(), 
            ])
            ->filters([
                SelectFilter::make('umrah_package_id')
                    ->label('Pilih Batch / Paket')
                    ->relationship('umrahPackage', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                // ExportAction::make()->exports([\pxlrbt\FilamentExcel\Exports\ExcelExport::make()]),
            ]);
    }
}

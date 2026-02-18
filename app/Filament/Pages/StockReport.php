<?php

namespace App\Filament\Pages;

use App\Models\InventoryItem;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use BackedEnum;
use Filament\Tables\Table;
use UnitEnum;

class StockReport extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected string $view = 'filament.pages.stock-report';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Stock';
    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(InventoryItem::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge(),

                TextColumn::make('stock_quantity')
                    ->label('Sisa Stok Fisik')
                    ->weight('bold')
                    ->color(fn ($state) => $state < 10 ? 'danger' : 'success')
                    ->description(fn ($state) => $state < 10 ? 'Stok Kritis!' : 'Aman'),

                TextColumn::make('movements_sum_quantity')
                    ->sum('movements', 'quantity') 
                    ->label('Total Keluar (Terpakai)'),
            ])
            ->headerActions([
                // ExportAction::make()->exports([\pxlrbt\FilamentExcel\Exports\ExcelExport::make()]),
            ]);
    }
}

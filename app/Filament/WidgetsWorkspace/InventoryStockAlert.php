<?php

namespace App\Filament\WidgetsWorkspace;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;

class InventoryStockAlert extends TableWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Peringatan Stok Gudang (Low Stock)';

    public static function canView(): bool
    {
        return Auth::user()->employee 
            && in_array(strtolower(Auth::user()->employee->departmentRel?->code), ['ops', 'log', 'ga']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryItem::query()
                    ->where('stock_quantity', '<=', 20) 
                    ->orderBy('stock_quantity', 'asc')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Barang')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (InventoryItem $record) => 'Tipe: ' . ucfirst($record->type)),

                TextColumn::make('stock_quantity')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->badge()
                    ->color(fn (int $state): string => $state <= 10 ? 'danger' : 'warning')
                    ->icon(fn (int $state): string => $state <= 10 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-information-circle'),

                TextColumn::make('status_text')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->stock_quantity <= 10 ? 'danger' : 'warning')
                    ->state(fn ($record) => $record->stock_quantity <= 10 ? 'KRITIS' : 'MENIPIS'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('restock')
                    ->label('Tambah Stok')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->button()
                    ->form([
                        TextInput::make('quantity_added')
                            ->label('Jumlah Masuk')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Masukkan jumlah barang yang baru dibeli.'),
                    ])
                    ->action(function (InventoryItem $record, array $data) {
                        $record->increment('stock_quantity', $data['quantity_added']);

                        Notification::make()
                            ->title('Stok Berhasil Ditambah')
                            ->body("Stok {$record->name} bertambah {$data['quantity_added']} pcs.")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}

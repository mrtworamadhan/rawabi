<?php

namespace App\Filament\Resources\InventoryItems;

use App\Filament\Resources\InventoryItems\Pages\ManageInventoryItems;
use App\Models\InventoryItem;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string | UnitEnum | null $navigationGroup = 'Logistik & Aset';
    protected static ?string $navigationLabel = 'Daftar Barang';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama Barang')
                                ->placeholder('Contoh: Koper 24 Inch, Kain Ihrom')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('stock_quantity')
                                ->label('Stok Saat Ini')
                                ->numeric()
                                ->default(0)
                                ->helperText('Masukkan jumlah fisik barang yang ada di gudang.'),

                            Select::make('type')
                                ->label('Jenis Peruntukan')
                                ->options([
                                    'umum' => 'Umum (Semua Jamaah)',
                                    'pria' => 'Khusus Pria (Ex: Ihrom)',
                                    'wanita' => 'Khusus Wanita (Ex: Mukena)',
                                ])
                                ->default('umum')
                                ->required(),
                        ])
                        
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->sortable()
                    ->color(fn (string $state): string => $state < 10 ? 'danger' : 'success') 
                    ->description(fn (InventoryItem $record): string => $record->stock_quantity < 10 ? 'Stok Menipis!' : ''),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'umum' => 'gray',
                        'pria' => 'info',   
                        'wanita' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'umum' => 'Umum',
                        'pria' => 'Pria',
                        'wanita' => 'Wanita',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryItems::route('/'),
        ];
    }
}

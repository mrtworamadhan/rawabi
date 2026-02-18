<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryMovements';
    protected static ?string $title = 'Pengambilan Perlengkapan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('inventory_item_id')
                    ->label('Barang')
                    ->relationship('inventoryItem', 'name') 
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->default(1)
                    ->required(),

                DatePicker::make('taken_date')
                    ->label('Tanggal Ambil')
                    ->default(now())
                    ->required(),
                
                TextInput::make('receiver_name')
                    ->label('Nama Pengambil')
                    ->placeholder('Isi jika diwakilkan')
                    ->helperText('Kosongkan jika diambil sendiri oleh Jamaah'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('inventoryItem.name')
                    ->label('Nama Barang')
                    ->sortable(),
                
                TextColumn::make('quantity')
                    ->label('Qty'),

                TextColumn::make('taken_date')
                    ->label('Tgl Ambil')
                    ->date('d M Y'),

                TextColumn::make('receiver_name')
                    ->label('Diambil Oleh')
                    ->placeholder('Ybs'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Input Pengambilan'),
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
}

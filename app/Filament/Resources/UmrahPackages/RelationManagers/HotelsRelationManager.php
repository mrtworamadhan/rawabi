<?php

namespace App\Filament\Resources\UmrahPackages\RelationManagers;

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
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HotelsRelationManager extends RelationManager
{
    protected static string $relationship = 'hotels';
    protected static ?string $title = 'Manifest Akomodasi (Hotel)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('hotel_name')
                    ->label('Nama Hotel')
                    ->required(),

                Select::make('city')
                    ->label('Kota')
                    ->options([
                        'Makkah' => 'Makkah',
                        'Madinah' => 'Madinah',
                        'Jeddah' => 'Jeddah',
                        'Istanbul' => 'Istanbul (Turki)',
                        'Dubai' => 'Dubai',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->required(),

                DatePicker::make('check_in')
                    ->label('Tanggal Check-In')
                    ->required(),

                DatePicker::make('check_out')
                    ->label('Tanggal Check-Out')
                    ->required(),
                
                Textarea::make('notes')
                    ->label('Catatan (Opsional)')
                    ->placeholder('Ex: Quad Share, View Kaabah')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('hotel_name')
            ->columns([
                TextColumn::make('city')
                    ->label('Kota')
                    ->sortable(),
                TextColumn::make('hotel_name')
                    ->label('Nama Hotel'),
                TextColumn::make('check_in')
                    ->date('d M Y'),
                TextColumn::make('check_out')
                    ->date('d M Y'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Hotel'),
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

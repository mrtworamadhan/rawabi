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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FlightsRelationManager extends RelationManager
{
    protected static string $relationship = 'flights';
    protected static ?string $title = 'Manifest Penerbangan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('airline')
                    ->label('Maskapai')
                    ->placeholder('Contoh: Saudia Airlines')
                    ->required()
                    ->maxLength(255),
                
                TextInput::make('flight_number')
                    ->label('Nomor Penerbangan')
                    ->placeholder('Contoh: SV-816')
                    ->required(),

                TextInput::make('depart_airport')
                    ->label('Bandara Asal (Kode)')
                    ->placeholder('CGK')
                    ->required()
                    ->maxLength(5),

                TextInput::make('arrival_airport')
                    ->label('Bandara Tujuan (Kode)')
                    ->placeholder('JED')
                    ->required()
                    ->maxLength(5),

                DateTimePicker::make('depart_at')
                    ->label('Waktu Berangkat')
                    ->required(),

                DateTimePicker::make('arrive_at')
                    ->label('Waktu Tiba')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('airline')
            ->columns([
                TextColumn::make('airline')
                    ->label('Maskapai'),
                TextColumn::make('flight_number')
                    ->label('No. Flight'),
                TextColumn::make('depart_airport')
                    ->label('Dari'),
                TextColumn::make('arrival_airport')
                    ->label('Ke'),
                TextColumn::make('depart_at')
                    ->label('Jam Berangkat')
                    ->dateTime('d M H:i'),
                TextColumn::make('arrive_at')
                    ->label('Jam Tiba')
                    ->dateTime('d M H:i'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Penerbangan'),
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

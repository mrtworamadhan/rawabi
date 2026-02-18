<?php

namespace App\Filament\Resources\UmrahPackages\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UmrahPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dasar Paket')
                    ->schema([
                       Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama Paket')
                                ->required()
                                ->placeholder('Contoh: Paket Syawal 2026')
                                ->maxLength(255),

                            TextInput::make('price')
                                ->label('Harga Jual')
                                ->required()
                                ->numeric()
                                ->prefix('IDR'),

                            TextInput::make('target_jamaah')
                                ->label('Kuota Kursi')
                                ->required()
                                ->numeric()
                                ->default(45),

                            Select::make('status')
                                ->options([
                                    'open' => 'Open Registration',
                                    'full' => 'Full Booked',
                                    'departed' => 'Sudah Berangkat',
                                    'completed' => 'Selesai',
                                ])
                                ->default('open')
                                ->required()
                       ])
                        
                    ])->columnSpanFull(),

                Section::make('Jadwal Keberangkatan')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('departure_date')
                                ->label('Tanggal Berangkat')
                                ->required(),

                            DatePicker::make('return_date')
                                ->label('Tanggal Pulang')
                                ->required()
                            ])
                    ])->columnSpanFull()
            ]);
    }
}

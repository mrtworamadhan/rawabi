<?php

namespace App\Filament\Resources\Jamaahs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JamaahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Buat Akun Login (Aplikasi Jamaah)')
                    ->description('Email dan Password untuk login jamaah ke aplikasi.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('email')
                                ->label('Email Jamaah')
                                ->email()
                                ->required()
                                ->unique(table: 'users', column: 'email', ignoreRecord: true),

                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->required(),
                        ])
                        
                    ])
                    ->columnSpanFull()
                    ->hiddenOn('edit'),

                Section::make('Data Pribadi')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nik')
                                ->label('NIK / No KTP')
                                ->numeric()
                                ->maxLength(16),
                            
                            TextInput::make('name')
                                ->label('Nama Lengkap')
                                ->required()
                                ->maxLength(255),
                            
                            Select::make('gender')
                                ->label('Jenis Kelamin')
                                ->options([
                                    'pria' => 'Pria',
                                    'wanita' => 'Wanita',
                                ])
                                ->required(),
                                
                            TextInput::make('phone')
                                ->label('No. WhatsApp')
                                ->tel()
                                ->required(),
                                
                            Textarea::make('address')
                                ->label('Alamat Lengkap')
                                ->columnSpanFull(),
                        ])
                        
                    ])->columnSpanFull(),

                Section::make('Data Dokumen')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('passport_number')
                                ->label('Nomor Paspor'),
                                
                            DatePicker::make('passport_expiry')
                                ->label('Tgl Kadaluarsa Paspor'),
                                
                            TextInput::make('shirt_size')
                                ->label('Ukuran Baju (S/M/L/XL/XXL)'),
                        ])
                        
                    ])->columnSpanFull(),
            ]);
    }
}

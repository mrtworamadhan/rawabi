<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Absensi Harian')
                    ->schema([
                        Grid::make(2)->schema([
                            Hidden::make('user_id')->default(auth()->id()),

                            DatePicker::make('date')
                                ->default(now())
                                ->readOnly()
                                ->required(),

                            TimePicker::make('clock_in_time')
                                ->label('Jam Masuk')
                                ->default(now())
                                ->seconds(false)
                                ->required(),

                            TimePicker::make('clock_out_time')
                                ->label('Jam Pulang')
                                ->helperText('Isi ini nanti saat mau pulang')
                                ->seconds(false),

                            Select::make('status')
                                ->options([
                                    'on_time' => 'Tepat Waktu',
                                    'late' => 'Terlambat',
                                    'permit' => 'Izin / Sakit',
                                ])
                                ->default('on_time')
                                ->required(),

                            FileUpload::make('clock_in_photo')
                                ->label('Foto Selfie (Bukti Kehadiran)')
                                ->image()
                                ->directory('attendances')
                                ->required()
                                ->columnSpanFull(),

                            Textarea::make('clock_in_location')
                                ->label('Lokasi GPS')
                                ->placeholder('Sistem akan mencatat lokasi...')
                                ->default('Lokasi Kantor') 
                                ->rows(2),
                        ])
                        
                    ])->columnSpanFull()
            ]);
    }
}

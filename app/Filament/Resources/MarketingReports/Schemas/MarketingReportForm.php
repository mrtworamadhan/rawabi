<?php

namespace App\Filament\Resources\MarketingReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MarketingReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('date')
                            ->label('Tanggal Kegiatan')
                            ->default(now())
                            ->required(),

                        Select::make('activity_type')
                            ->label('Jenis Kegiatan')
                            ->options([
                                'canvasing' => 'Canvasing / Sebar Brosur',
                                'follow_up' => 'Follow Up Jamaah',
                                'meeting' => 'Meeting Klien / Korporat',
                                'closing' => 'Closing / Terima DP',
                                'admin' => 'Administrasi Kantor',
                            ])
                            ->required(),

                        TextInput::make('location_name')
                            ->label('Lokasi')
                            ->placeholder('Contoh: Dinas Pendidikan, Masjid Raya')
                            ->required(),

                        TextInput::make('prospect_qty')
                            ->label('Jumlah Prospek Didapat')
                            ->numeric()
                            ->default(0)
                            ->helperText('Berapa nomor WA/kontak baru yang didapat?'),

                        Textarea::make('description')
                            ->label('Detail Laporan')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('photo_evidence')
                            ->label('Foto Kegiatan')
                            ->image()
                            ->directory('marketing-reports')
                            ->columnSpanFull(),
                            
                        Hidden::make('user_id')
                            ->default(auth()->id()),
                    ])
                    
                ])->columnSpanFull()
            ]);
    }
}

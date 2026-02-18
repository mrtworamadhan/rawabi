<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\UmrahPackage;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Transaksi')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('booking_code')
                                ->default('RZ-' . strtoupper(uniqid()))
                                ->disabled()
                                ->dehydrated() 
                                ->required(),

                            DatePicker::make('created_at')
                                ->label('Tanggal Booking')
                                ->default(now()),

                            Select::make('jamaah_id')
                                ->relationship('jamaah', 'name')
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')->required(),
                                    TextInput::make('phone')->required(),
                                    Select::make('gender')->options(['pria'=>'Pria','wanita'=>'Wanita'])->required(),
                                ])
                                ->required(),

                            Select::make('umrah_package_id')
                                ->label('Pilih Paket Umroh')
                                ->relationship('umrahPackage', 'name') 
                                ->live() 
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    if ($state) {
                                        $paket = UmrahPackage::find($state);
                                        if ($paket) {
                                            $set('total_price', $paket->price);
                                        }
                                    }
                                })
                                ->required(),
                            
                            TextInput::make('total_price')
                                ->label('Harga Deal / Total Tagihan')
                                ->numeric()
                                ->prefix('IDR')
                                ->required(),

                            Select::make('status')
                                ->options([
                                    'booking' => 'Baru Booking',
                                    'dp_paid' => 'Sudah DP',
                                    'paid_in_full' => 'Lunas',
                                    'cancelled' => 'Batal',
                                    'reschedule' => 'Reschedule',
                                ])
                                ->default('booking')
                                ->required(),
                        ])
                        
                    ])->columnSpanFull(),

                Section::make('Data Sales / Referensi')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('sales_id')
                                ->label('Sales Internal (Staff)')
                                ->relationship('sales', 'full_name')
                                ->default(fn() => auth()->user()->employee?->id),
                            
                            Select::make('agent_id')
                                ->label('Agen Referensi (Jika ada)')
                                ->relationship('agent', 'name')
                                ->searchable()
                                ->placeholder('Pilih Agen jika dari luar struktur'),

                            Textarea::make('notes')
                                ->label('Catatan Khusus')
                                ->columnSpanFull(),
                        ])
                    ])->columnSpanFull(),
                
                Section::make('Pembayaran DP (Down Payment)')
                    ->description('Wajib input DP minimal Rp 10.000.000 sesuai SOP.')
                    ->visibleOn('create')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('dp_amount')
                                ->label('Nominal DP')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(10000000)
                                ->minValue(10000000)
                                ->required(),
                            
                            Select::make('dp_method')
                                ->label('Metode Pembayaran')
                                ->options([
                                    'transfer' => 'Transfer Bank',
                                    'cash' => 'Tunai / Cash',
                                ])
                                ->required(),

                            FileUpload::make('dp_proof')
                                ->label('Bukti Transfer / Kuitansi')
                                ->image()
                                ->directory('payments')
                                ->required()
                                ->columnSpanFull(),
                        ])
                    ]),
            ]);
    }
}

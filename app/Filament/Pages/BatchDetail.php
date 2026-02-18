<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\InventoryMovement;
use App\Models\UmrahPackage;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class BatchDetail extends Page implements HasTable, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithInfolists;
    use HasPageShield;

    protected string $view = 'filament.pages.batch-detail';
    public ?UmrahPackage $record = null;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $id = request()->query('record');
        $this->record = UmrahPackage::findOrFail($id);
        
        static::$title = 'Laporan Batch: ' . $this->record->name;
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Informasi Paket & Akomodasi')
                    ->schema([
                        Section::make('')->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('departure_date')
                                    ->label('Tgl Berangkat')
                                    ->date('d M Y')
                                    ->icon('heroicon-m-calendar'),
                                    
                                TextEntry::make('return_date')
                                    ->label('Tgl Pulang')
                                    ->date('d M Y')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('price')
                                    ->label('Harga Jual')
                                    ->money('IDR')
                                    ->weight('bold'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'open' => 'success',
                                        'full' => 'warning',
                                        'departed' => 'info',
                                        'completed' => 'gray',
                                        default => 'gray',
                                    }),         
                            ])
                        
                        ]),
                        Grid::make(2)->schema([
                        
                            Section::make('Jadwal Penerbangan')
                                ->icon('heroicon-m-paper-airplane')
                                ->compact()
                                ->schema([
                                    
                                    RepeatableEntry::make('flights')
                                        ->label('')
                                        ->schema([
                                            Section::make('')->schema([
                                                Grid::make(2)->schema([
                                                    TextEntry::make('airline')
                                                        ->label('Maskapai')
                                                        ->weight('bold')
                                                        ->icon('heroicon-m-paper-airplane'),
                                                        
                                                    TextEntry::make('flight_number')
                                                        ->label('No. Flight'),
                                                ]),
                                                
                                                TextEntry::make('rute')
                                                    ->label('Rute')
                                                    ->state(fn ($record) => $record->depart_airport . ' ➝ ' . $record->arrival_airport)
                                                    ->weight('medium'),

                                                Grid::make(2)->schema([
                                                    TextEntry::make('depart_at')
                                                        ->label('Berangkat')
                                                        ->date('d M H:i'),
                                                    TextEntry::make('arrive_at')
                                                        ->label('Tiba')
                                                        ->date('d M H:i'),
                                                ]),
                                            ])
                                            
                                        ])
                                        ->contained(false) 
                                ])->columnSpan(1),

                            Section::make('Akomodasi Hotel')
                                ->icon('heroicon-m-building-office-2')
                                ->compact()
                                ->schema([
                                    RepeatableEntry::make('hotels')
                                        ->label('')
                                        ->schema([
                                            Section::make()->schema([
                                                Grid::make(2)->schema([
                                                    TextEntry::make('city')
                                                        ->label('Kota')
                                                        ->badge()
                                                        ->color(fn ($state) => $state == 'Makkah' ? 'warning' : 'success'), // Makkah Emas, Madinah Hijau

                                                    TextEntry::make('hotel_name')
                                                        ->label('Nama Hotel')
                                                        ->weight('bold'),
                                                ]),

                                                TextEntry::make('check_in')
                                                    ->label('Durasi Menginap')
                                                    ->state(fn ($record) => 
                                                        Carbon::parse($record->check_in)->format('d M') . ' - ' . 
                                                        Carbon::parse($record->check_out)->format('d M Y')
                                                    )
                                                    ->icon('heroicon-m-clock'),
                                            ])
                                            
                                        ])
                                        ->contained(false)
                                ])->columnSpan(1),
                        ]),
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->where('umrah_package_id', $this->record->id)
                    ->with(['jamaah', 'sales', 'agent', 'payments'])
            )
            ->heading('Data Jamaah')
            ->columns([
                TextColumn::make('jamaah.name')
                    ->label('Nama Jamaah')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->booking_code),

                TextColumn::make('payment_info')
                    ->label('Tagihan & Status')
                    ->state(function (Booking $record) {
                        if ($record->status === 'paid_in_full') {
                            return 'LUNAS';
                        }

                        $paid = $record->payments->whereNotNull('verified_at')->sum('amount');
                        $sisa = $record->total_price - $paid;

                        if ($sisa <= 100) { 
                            return 'LUNAS'; 
                        }

                        return 'Kurang: ' . number_format($sisa, 0, ',', '.');
                    })
                    ->badge()
                    ->color(fn (string $state) => $state === 'LUNAS' ? 'success' : 'danger')
                    ->description(fn (Booking $record) => match($record->status) {
                        'dp_paid' => 'Sudah DP',
                        'booking' => 'Baru Booking',
                        'cancelled' => 'Dibatalkan',
                        default => null
                    }),

                IconColumn::make('jamaah.passport_number')
                    ->label('Paspor')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->tooltip(fn ($state) => $state ? 'Lengkap' : 'Paspor Belum Ada!'),

                TextColumn::make('vaccine_status')
                    ->label('Vaksin')
                    ->default('Belum') 
                    ->badge()
                    ->color('warning'),

                TextColumn::make('equipment_status')
                    ->label('Koper')
                    ->state(fn ($record) => InventoryMovement::where('booking_id', $record->id)->exists() ? 'Diambil' : 'Belum')
                    ->badge()
                    ->color(fn ($state) => $state === 'Diambil' ? 'success' : 'gray'),

                TextColumn::make('pic_info')
                    ->label('PIC (Sales & Agen)')
                    ->state(function (Booking $record) {
                        $parts = [];

                        if ($record->sales_id) {
                            $parts[] = 'Sales: ' . $record->sales->full_name;
                        }

                        if ($record->agent_id) {
                            $parts[] = 'Agen: ' . $record->agent->name;
                        }

                        return empty($parts) ? '-' : implode(' | ', $parts);
                    })
                    ->badge()
                    ->color('info')
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'paid_in_full' => 'Lunas',
                        'dp_paid' => 'Belum Lunas (DP)',
                        'booking' => 'Baru Booking',
                    ]),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\WidgetsBatch\BatchDetailStats::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\WidgetsBatch\BatchDetailMarketingTable::class,
            \App\Filament\WidgetsBatch\BatchDetailAgentTable::class,
        ];
    }

}

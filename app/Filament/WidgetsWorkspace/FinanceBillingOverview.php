<?php

namespace App\Filament\WidgetsWorkspace;

use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FinanceBillingOverview extends TableWidget
{
    protected static ?int $sort = 3; // Taruh dibawah Tugas
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Monitoring Tagihan Jamaah (Piutang)';

    public static function canView(): bool
    {
        return Auth::user()->employee 
            && in_array(strtolower(Auth::user()->employee->departmentRel?->code), ['fin', 'finance']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->join('umrah_packages', 'bookings.umrah_package_id', '=', 'umrah_packages.id')
                    ->select('bookings.*')
                    ->withSum(['payments' => function ($query) {
                        $query->whereNotNull('verified_at');
                    }], 'amount')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->where('bookings.status', '!=', 'paid_in_full')
                    ->whereDate('umrah_packages.departure_date', '>=', now())
                    ->orderBy('umrah_packages.departure_date', 'asc')
            )
            ->columns([
                TextColumn::make('jamaah.name')
                    ->label('Nama Jamaah')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Booking $record) => $record->umrahPackage->name ?? '-'),

                TextColumn::make('umrahPackage.departure_date')
                    ->label('Keberangkatan')
                    ->date('d M Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state <= now()->addDays(30) ? 'danger' : 'warning'),
                TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->color('gray'),

                TextColumn::make('payments_sum_amount') 
                    ->label('Sudah Bayar')
                    ->money('IDR')
                    ->default(0)
                    ->color('success'),

                TextColumn::make('deficiency')
                    ->label('Kurang Bayar')
                    ->money('IDR')
                    ->state(function (Booking $record) {
                        return $record->total_price - ($record->payments_sum_amount ?? 0);
                    })
                    ->weight('bold')
                    ->color('danger')
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('remind_payment')
                    ->label('Tagih WA')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('success')
                    ->button()
                    ->url(function (Booking $record) {
                        $phone = $record->jamaah->phone;
                        if (str_starts_with($phone, '0')) $phone = '62' . substr($phone, 1);
                        
                        $paid = $record->payments_sum_amount ?? 0;
                        $sisa = number_format($record->total_price - $paid, 0, ',', '.');
                        
                        $tgl = $record->umrahPackage 
                            ? \Carbon\Carbon::parse($record->umrahPackage->departure_date)->format('d M Y') 
                            : '-';
                        
                        $text = "Assalamu'alaikum Bpk/Ibu {$record->jamaah->name}. Mengingatkan sisa pembayaran Umrah sebesar Rp {$sisa} untuk keberangkatan tgl {$tgl}. Mohon segera diselesaikan. Terima kasih - Finance Rawabi.";
                        
                        return "https://wa.me/{$phone}?text=" . urlencode($text);
                    }, shouldOpenInNewTab: true),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ])
            ->paginated([5, 10]);
    }
}

<?php

namespace App\Filament\WidgetsBatch;

use App\Models\UmrahPackage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Url; // WAJIB IMPORT INI

class BatchDetailStats extends BaseWidget
{
    protected ?string $pollingInterval = '5s';

    #[Url(as: 'record', keep: true)] 
    public $recordId = null;

    protected function getStats(): array
    {
        if (!$this->recordId) {
            return [
                Stat::make('Error', 'ID Paket Hilang')
                    ->description('Pastikan URL mengandung ?record=ID')
                    ->color('danger')
            ];
        }

        $package = UmrahPackage::with(['bookings.payments'])->find($this->recordId);

        if (!$package) {
            return [
                Stat::make('Error', 'Data Paket Tidak Ditemukan di DB')
                    ->description('ID: ' . $this->recordId)
                    ->color('danger')
            ];
        }

        
        $terisi = $package->bookings->where('status', '!=', 'cancelled')->count();
        $target = $package->target_jamaah;
        $persen = $target > 0 ? round(($terisi / $target) * 100) : 0;

        $potensi = $package->bookings->where('status', '!=', 'cancelled')->sum('total_price');
        
        $terkumpul = $package->bookings->sum(function ($booking) {
            return $booking->payments->whereNotNull('verified_at')->sum('amount');
        });

        $piutang = $potensi - $terkumpul;

        return [
            Stat::make('Jamaah vs Kuota', "{$terisi} / {$target} Pax")
                ->description("Terisi {$persen}%")
                ->chart([$persen, 100])
                ->color($persen >= 100 ? 'warning' : 'success'),

            Stat::make('Total Dana Masuk', 'IDR ' . number_format($terkumpul, 0, ',', '.'))
                ->description('Potensi: ' . number_format($potensi, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Total Piutang', 'IDR ' . number_format($piutang, 0, ',', '.'))
                ->description('Belum Lunas')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($piutang > 0 ? 'danger' : 'success'),
        ];
    }
}
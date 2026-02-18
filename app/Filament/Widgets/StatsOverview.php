<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Jamaah;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    use HasWidgetShield;
    
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $omsetBulanIni = Booking::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $expenseBulanIni = Expense::whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->where('status', 'approved')
            ->sum('amount');

        $profit = $omsetBulanIni - $expenseBulanIni;

        $jamaahBaru = Jamaah::whereMonth('created_at', Carbon::now()->month)->count();

        return [
            Stat::make('Omset Bulan Ini', 'IDR ' . number_format($omsetBulanIni, 0, ',', '.'))
                ->description('Total Booking Masuk')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Pengeluaran', 'IDR ' . number_format($expenseBulanIni, 0, ',', '.'))
                ->description('Biaya Operasional Approved')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([3, 5, 2, 8, 1, 9, 3]),

            Stat::make('Laba Bersih (Estimasi)', 'IDR ' . number_format($profit, 0, ',', '.'))
                ->description('Omset - Pengeluaran')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($profit >= 0 ? 'primary' : 'danger'),

            Stat::make('Jamaah Baru', $jamaahBaru . ' Orang')
                ->description('Pendaftar Bulan Ini')
                ->color('info'),
        ];
    }
}
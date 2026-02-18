<?php

namespace App\Filament\WidgetsWorkspace;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PersonalPerformanceStats extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->employee && 
               strtolower(auth()->user()->employee->department) === 'marketing';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) return [];

        $targetObj = \App\Models\SalesTarget::where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', now()) 
            ->whereDate('end_date', '>=', now())   
            ->latest() 
            ->first();

        $targetAngka = $targetObj ? $targetObj->target_jamaah : 0;

        $queryRealisasi = \App\Models\Booking::where('sales_id', $employee->id)
            ->where('status', '!=', 'cancelled');

        if ($targetObj) {
            $queryRealisasi->whereBetween('created_at', [
                $targetObj->start_date, 
                $targetObj->end_date . ' 23:59:59'
            ]);
        } else {
            $queryRealisasi->whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year);
        }

        $realisasi = $queryRealisasi->count();

        $persen = $targetAngka > 0 ? ($realisasi / $targetAngka) * 100 : 0;
        
        $color = 'danger';
        if ($persen >= 100) $color = 'success';
        elseif ($persen >= 50) $color = 'warning';

        $icon = $persen >= 100 ? 'heroicon-m-trophy' : 'heroicon-m-arrow-trending-up';

        return [
            Stat::make('Target Penjualan Saya', "{$realisasi} / {$targetAngka} Jamaah")
                ->description(number_format($persen, 1) . '% Tercapai')
                ->descriptionIcon($icon)
                ->chart([ 
                    $realisasi > 0 ? 10 : 0, 
                    $persen > 20 ? 30 : 10, 
                    $persen > 50 ? 60 : 20, 
                    $persen >= 100 ? 100 : $persen
                ])
                ->color($color),
        ];
    }
}
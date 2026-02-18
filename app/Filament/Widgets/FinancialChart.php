<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Expense;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class FinancialChart extends ChartWidget
{
    use HasWidgetShield;
    protected ?string $heading = 'Arus Kas Tahun Ini';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $incomeData = Trend::query(
            Booking::query()->where('status', '!=', 'cancelled') 
        )
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth()
        ->sum('total_price');

        $expenseData = Trend::query(
            Expense::query()->where('status', 'approved') 
        )
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth()
        ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan (Omset)',
                    'data' => $incomeData->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10b981', 
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)', 
                    'fill' => true,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expenseData->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#ef4444', 
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)', 
                    'fill' => true,
                ],
            ],
            'labels' => $incomeData->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
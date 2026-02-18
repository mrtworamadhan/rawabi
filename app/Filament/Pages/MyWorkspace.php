<?php

namespace App\Filament\Pages;

use App\Filament\WidgetsWorkspace\InventoryStockAlert;
use App\Filament\WidgetsWorkspace\MyDailyTasks;
use App\Filament\WidgetsWorkspace\PersonalPerformanceStats;
use App\Filament\WidgetsWorkspace\FinanceBillingOverview;
use App\Models\Task;
use App\Models\TaskTemplate;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MyWorkspace extends Page
{
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;
    protected static ?string $navigationLabel = 'Ruang Kerja Saya';
    protected static ?int $navigationSort = -1;
    protected string $view = 'filament.pages.my-workspace';


    protected function getHeaderWidgets(): array
    {
        return [
            PersonalPerformanceStats::class,
            MyDailyTasks::class,
            FinanceBillingOverview::class,
            InventoryStockAlert::class,
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $count = Task::where('employee_id', Auth::user()->employee?->id)
            ->where('status', 'pending')
            ->count();
            
        return $count > 0 ? (string) $count : null;
    }
}

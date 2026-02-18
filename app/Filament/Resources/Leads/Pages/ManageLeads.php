<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Booking;
use App\Models\Lead;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageLeads extends ManageRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Calon Jamaah Baru'),
        ];
    }

    public function getTabs(): array
    {
        $salesId = auth()->user()->employee?->id;

        return [
            'all' => Tab::make('Semua')
                ->icon('heroicon-m-users'),

            'followUp' => Tab::make('Butuh Follow Up')
                ->icon('heroicon-m-clock')
                ->badgeColor('warning')
                ->badge(fn () => Lead::query()
                    ->where('sales_id', $salesId)
                    ->whereIn('status', ['cold', 'warm', 'hot'])
                    ->count()
                )
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['cold', 'warm', 'hot'])),

            'deal' => Tab::make('Deal / Closing')
                ->icon('heroicon-m-check-badge')
                ->badgeColor('success')
                ->badge(fn () => Lead::query()
                    ->where('sales_id', $salesId)
                    ->whereIn('status', ['closing', 'converted'])
                    ->count()
                )
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['closing', 'converted'])),

            'lost' => Tab::make('Gagal / Batal')
                ->icon('heroicon-m-x-circle')
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'lost')), // Kalau cuma 1, boleh pakai where biasa
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'followUp';
    }
    
}

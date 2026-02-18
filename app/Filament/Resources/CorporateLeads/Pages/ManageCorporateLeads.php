<?php

namespace App\Filament\Resources\CorporateLeads\Pages;

use App\Filament\Resources\CorporateLeads\CorporateLeadResource;
use App\Models\CorporateLead;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageCorporateLeads extends ManageRecords
{
    protected static string $resource = CorporateLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $salesId = auth()->user()->employee?->id;

        return [
            'all' => Tab::make('Semua')
                ->icon('heroicon-m-building-office'),

            'followUp' => Tab::make('Butuh Follow Up')
                ->icon('heroicon-m-clock')
                ->badgeColor('warning')
                ->badge(fn () => CorporateLead::query()
                    ->where('sales_id', $salesId)
                    ->whereIn('status', ['prospecting', 'presentation', 'negotiation'])
                    ->count()
                )
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['prospecting', 'presentation', 'negotiation'])),

            'deal' => Tab::make('Deal / Closing')
                ->icon('heroicon-m-check-badge')
                ->badgeColor('success')
                ->badge(fn () => CorporateLead::query()
                    ->where('sales_id', $salesId)
                    ->where('status', 'deal')
                    ->count()
                )
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'deal')),

            'lost' => Tab::make('Gagal / Batal')
                ->icon('heroicon-m-x-circle')
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'lost')), 
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'followUp';
    }
}

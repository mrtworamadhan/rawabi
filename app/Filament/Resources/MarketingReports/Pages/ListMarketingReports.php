<?php

namespace App\Filament\Resources\MarketingReports\Pages;

use App\Filament\Resources\MarketingReports\MarketingReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketingReports extends ListRecords
{
    protected static string $resource = MarketingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Laporan'),
        ];
    }
}

<?php

namespace App\Filament\Resources\MarketingReports\Pages;

use App\Filament\Resources\MarketingReports\MarketingReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketingReport extends EditRecord
{
    protected static string $resource = MarketingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

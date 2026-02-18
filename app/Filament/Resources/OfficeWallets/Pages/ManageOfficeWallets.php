<?php

namespace App\Filament\Resources\OfficeWallets\Pages;

use App\Filament\Resources\OfficeWallets\OfficeWalletResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOfficeWallets extends ManageRecords
{
    protected static string $resource = OfficeWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

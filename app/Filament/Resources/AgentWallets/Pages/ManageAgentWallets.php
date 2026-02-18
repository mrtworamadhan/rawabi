<?php

namespace App\Filament\Resources\AgentWallets\Pages;

use App\Filament\Resources\AgentWallets\AgentWalletResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAgentWallets extends ManageRecords
{
    protected static string $resource = AgentWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Jamaahs\Pages;

use App\Filament\Resources\Jamaahs\JamaahResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJamaah extends EditRecord
{
    protected static string $resource = JamaahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

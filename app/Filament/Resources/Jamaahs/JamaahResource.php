<?php

namespace App\Filament\Resources\Jamaahs;

use App\Filament\Resources\Jamaahs\Pages\CreateJamaah;
use App\Filament\Resources\Jamaahs\Pages\EditJamaah;
use App\Filament\Resources\Jamaahs\Pages\ListJamaahs;
use App\Filament\Resources\Jamaahs\Schemas\JamaahForm;
use App\Filament\Resources\Jamaahs\Tables\JamaahsTable;
use App\Models\Jamaah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JamaahResource extends Resource
{
    protected static ?string $model = Jamaah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return JamaahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JamaahsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJamaahs::route('/'),
            'create' => CreateJamaah::route('/create'),
            'edit' => EditJamaah::route('/{record}/edit'),
        ];
    }
}

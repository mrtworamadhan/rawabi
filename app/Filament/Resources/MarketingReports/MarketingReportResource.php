<?php

namespace App\Filament\Resources\MarketingReports;

use App\Filament\Resources\MarketingReports\Pages\CreateMarketingReport;
use App\Filament\Resources\MarketingReports\Pages\EditMarketingReport;
use App\Filament\Resources\MarketingReports\Pages\ListMarketingReports;
use App\Filament\Resources\MarketingReports\Schemas\MarketingReportForm;
use App\Filament\Resources\MarketingReports\Tables\MarketingReportsTable;
use App\Models\MarketingReport;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarketingReportResource extends Resource
{
    protected static ?string $model = MarketingReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | UnitEnum | null $navigationGroup = 'Workspace';
    protected static ?string $navigationLabel = 'Kegiatan Marketing';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(!auth()->user()->hasRole('super_admin'), fn ($query) => $query->where('user_id', auth()->id()));
    }

    public static function form(Schema $schema): Schema
    {
        return MarketingReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketingReportsTable::configure($table);
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
            'index' => ListMarketingReports::route('/'),
            'create' => CreateMarketingReport::route('/create'),
            'edit' => EditMarketingReport::route('/{record}/edit'),
        ];
    }
}

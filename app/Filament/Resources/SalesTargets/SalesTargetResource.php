<?php

namespace App\Filament\Resources\SalesTargets;

use App\Filament\Resources\SalesTargets\Pages\ManageSalesTargets;
use App\Models\SalesTarget;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SalesTargetResource extends Resource
{
    protected static ?string $model = SalesTarget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string | UnitEnum | null $navigationGroup = 'Manajemen SDM';
    protected static ?string $navigationLabel = 'Target Sales';
    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting Target Bulanan')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan Target')
                            ->relationship('employee', 'full_name') 
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('target_jamaah')
                            ->label('Target Jumlah Jamaah')
                            ->numeric()
                            ->default(20)
                            ->required(),

                        TextInput::make('target_omset')
                            ->label('Target Omset (Rp)')
                            ->numeric()
                            ->prefix('IDR'),

                        DatePicker::make('start_date')
                            ->label('Mulai Tanggal')
                            ->default(now()->startOfMonth())
                            ->required(),

                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->default(now()->endOfMonth())
                            ->required(),

                        Hidden::make('set_by')
                            ->default(fn() => Auth::user()->id),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Marketing')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Periode')
                    ->date('M Y'), 

                TextColumn::make('target_jamaah')
                    ->label('Target Jamaah')
                    ->formatStateUsing(fn ($state) => $state . ' Orang')
                    ->sortable(),
                    
                TextColumn::make('target_omset')
                    ->label('Target Omset')
                    ->money('IDR')
                    ->default('-'),
            ])
            ->filters([
                Filter::make('bulan_ini')
                    ->query(fn ($query) => $query->whereMonth('start_date', now()->month)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSalesTargets::route('/'),
        ];
    }
}

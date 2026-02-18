<?php

namespace App\Filament\Resources\Agents;

use App\Filament\Resources\Agents\Pages\ManageAgents;
use App\Models\Agent;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use UnitEnum;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string | UnitEnum | null $navigationGroup = 'Manajemen Agen';
    protected static ?string $navigationLabel = 'Daftar Agen';
    protected static ?int $navigationSort = 1;


    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Agen')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->tel()
                                ->required(),
                            TextInput::make('email')
                                ->email(),
                            Textarea::make('address'),
                        ])
                    ])->columnSpanFull(),
                Section::make('Informasi Agen')
                    ->description('Pengaturan sales pembina dan nilai komisi khusus.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('sales_id')
                                ->label('Sales Pembina')
                                ->relationship('sales', 'full_name') 
                                ->searchable()
                                ->preload()
                                ->helperText('Sales yang merekrut/membina agen ini.'),

                            TextInput::make('commission_override')
                                ->label('Komisi Khusus (Override)')
                                ->numeric()
                                ->prefix('Rp')
                                ->helperText('Isi HANYA jika komisi agen ini beda dari standar kantor. Kosongkan jika ikut standar.'),
                        ])
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Kontak'),
                TextColumn::make('bookings_count')
                    ->counts('bookings')
                    ->label('Total Jamaah')
                    ->sortable(),
                TextColumn::make('sales.full_name')
                    ->label('Sales Pembina'),
            ])
            ->defaultSort('bookings_count', 'desc')
            ->filters([
                //
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
            'index' => ManageAgents::route('/'),
        ];
    }
}

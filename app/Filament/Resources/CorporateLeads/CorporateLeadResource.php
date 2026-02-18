<?php

namespace App\Filament\Resources\CorporateLeads;

use App\Filament\Resources\CorporateLeads\Pages\ManageCorporateLeads;
use App\Models\CorporateLead;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CorporateLeadResource extends Resource
{
    protected static ?string $model = CorporateLead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string | UnitEnum | null $navigationGroup = 'Marketing & Sales';
    protected static ?string $navigationLabel = 'Corporate/Instansi';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->employee) {
            $query->where('sales_id', $user->employee->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Instansi')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nama Instansi / PT / Majelis')
                            ->required(),
                        
                        TextInput::make('address')
                            ->label('Alamat')
                            ->maxLength(255),

                        TextInput::make('pic_name')
                            ->label('Nama PIC (Penanggung Jawab)')
                            ->required(),
                        
                        TextInput::make('pic_phone')
                            ->label('No HP PIC')
                            ->tel()
                            ->required(),
                    ])->columns(2),

                Section::make('Potensi Deal')
                    ->schema([
                        TextInput::make('potential_pax')
                            ->label('Estimasi Jamaah')
                            ->numeric()
                            ->suffix('Orang')
                            ->required(),

                        TextInput::make('budget_estimation')
                            ->label('Estimasi Budget Total')
                            ->numeric()
                            ->prefix('Rp')
                            ->nullable(),

                        Select::make('status')
                            ->options([
                                'prospecting' => 'Prospecting (PDKT)',
                                'presentation' => 'Presentasi',
                                'negotiation' => 'Negosiasi Harga',
                                'deal' => 'DEAL',
                                'lost' => 'Lost (Gagal)',
                            ])
                            ->default('prospecting')
                            ->required(),

                        Select::make('sales_id')
                            ->relationship('sales', 'full_name')
                            ->default(fn () => auth()->user()->employee?->id)
                            ->required(),
                        
                        Textarea::make('notes')
                            ->label('Progress Note')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Instansi')
                    ->searchable()
                    ->weight('bold'),
                
                TextColumn::make('pic_name')
                    ->label('PIC')
                    ->description(fn (CorporateLead $record) => $record->pic_phone),

                TextColumn::make('potential_pax')
                    ->label('Potensi Pax')
                    ->badge(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'prospecting',
                        'info' => 'presentation',
                        'primary' => 'negotiation',
                        'success' => 'deal',
                        'danger' => 'lost',
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('wa_pic')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (CorporateLead $record) => "https://wa.me/" . $record->pic_phone, true),
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
            'index' => ManageCorporateLeads::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\TaskTemplates;

use App\Filament\Resources\TaskTemplates\Pages\ManageTaskTemplates;
use App\Models\TaskTemplate;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaskTemplateResource extends Resource
{
    protected static ?string $model = TaskTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string | UnitEnum | null $navigationGroup = 'Manajemen SDM';
    protected static ?string $navigationLabel = 'Job Desk';
    protected static ?int $navigationSort = 6;


    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Definisi SOP')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('department_id')
                                ->relationship('department', 'name')
                                ->required(),

                            TextInput::make('title')
                                ->label('Nama Tugas')
                                ->placeholder('Contoh: Laporan Harian Marketing')
                                ->required(),

                            Select::make('frequency')
                                ->label('Frekuensi')
                                ->options([
                                    'daily' => 'Harian (Setiap Hari)',
                                    'weekly' => 'Mingguan (Setiap Senin)',
                                    'monthly' => 'Bulanan (Tgl 1)',
                                    'incidental' => 'Insidentil (Manual)',
                                ])
                                ->required(),

                            TimePicker::make('deadline_time')
                                ->label('Batas Waktu (Jam)')
                                ->default('17:00'),

                            Textarea::make('description')
                                ->label('Deskripsi SOP')
                                ->helperText('Jelaskan detail apa yang harus dilakukan karyawan')
                                ->columnSpanFull(),
                            
                            Toggle::make('is_mandatory')
                                ->label('Wajib Dikerjakan')
                                ->default(true),
                                
                            Toggle::make('is_active')
                                ->label('Aktif?')
                                ->default(true),
                        ])
                        
                    ])->columnSpanFull(),
                Section::make('Tujuan Pengerjaan (Auto Link)')
                    ->description('Pilih menu tujuan. Link akan terisi otomatis.')
                    ->schema([
                        Grid::make(3)->schema([
                            
                            Select::make('target_resource')
                                ->label('Pilih Menu')
                                ->options(function () {
                                    $resources = Filament::getResources();
                                    $options = [];
                                    foreach ($resources as $resource) {
                                        $options[$resource] = $resource::getNavigationLabel();
                                    }
                                    return $options;
                                })
                                ->searchable()
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(fn (Set $set, Get $get) => self::generateUrl($set, $get)),

                            Select::make('target_page')
                                ->label('Halaman')
                                ->options([
                                    'index' => 'List Data (Tabel)',
                                    'create' => 'Form Buat Baru',
                                ])
                                ->default('index')
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(fn (Set $set, Get $get) => self::generateUrl($set, $get)),

                            TextInput::make('target_tab')
                                ->label('Nama Tab (Khusus List)')
                                ->placeholder('Contoh: followUp')
                                ->helperText('Isi sesuai nama Tab yang ada di halaman tujuan. Biarkan kosong jika tidak ada tab.')
                                ->visible(fn (Get $get) => $get('target_page') === 'index')
                                ->live(onBlur: true) 
                                ->dehydrated(false)
                                ->afterStateUpdated(fn (Set $set, Get $get) => self::generateUrl($set, $get)),
                        ]),

                        TextInput::make('action_url')
                            ->label('Generated URL')
                            ->readOnly()
                            ->required()
                            ->prefixIcon('heroicon-m-link')
                            ->helperText('URL ini terisi otomatis berdasarkan pilihan di atas.'),
                    ])->columnSpanFull(),
            ]);
    }
    public static function generateUrl(Set $set, Get $get)
    {
        $resource = $get('target_resource');
        $page = $get('target_page');
        $tab = $get('target_tab');

        if (!$resource || !$page) {
            return;
        }

        try {
            $url = $resource::getUrl($page);

            if ($page === 'index' && filled($tab)) {
                $url .= '?activeTab=' . $tab;
            }

            $set('action_url', $url);
            
        } catch (\Exception $e) {
            $set('action_url', null);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('department.name')->badge(),
                TextColumn::make('frequency')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'daily' => 'success',
                        'weekly' => 'info',
                        'monthly' => 'warning',
                        'adhoc' => 'gray',
                    }),
                IconColumn::make('is_mandatory')->boolean(),    
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label(''),
                DeleteAction::make()->label(''),
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
            'index' => ManageTaskTemplates::route('/'),
        ];
    }
}

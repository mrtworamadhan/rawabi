<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Jamaahs\JamaahResource;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Models\Booking;
use App\Models\Jamaah;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
use Filament\Schemas\Components\Tabs\Tab;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFunnel;

    protected static string | UnitEnum | null $navigationGroup = 'Marketing & Sales';
    protected static ?string $navigationLabel = 'Calon Jamaah';
    protected static ?int $navigationSort = 1;

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
                Section::make('Informasi Prospek')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Calon')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('phone')
                            ->label('Nomor WhatsApp')
                            ->tel()
                            ->required()
                            ->prefix('+62') 
                            ->placeholder('812xxxxxxx')
                            ->maxLength(20),

                        TextInput::make('city')
                            ->label('Kota Domisili')
                            ->maxLength(255),

                        Select::make('source')
                            ->label('Sumber Info')
                            ->options([
                                'Facebook Ads' => 'Facebook Ads',
                                'Instagram' => 'Instagram',
                                'Tiktok' => 'Tiktok',
                                'Website' => 'Website',
                                'Agent' => 'Referensi Agen',
                                'Walk-in' => 'Datang ke Kantor',
                                'Referral' => 'Referral Teman',
                            ])
                            ->required()
                            ->reactive(),
                    ])->columns(2),

                Section::make('Detail Sales & Status')
                    ->schema([
                        Select::make('agent_id')
                            ->label('Nama Agen Referensi')
                            ->relationship('agent', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('source') === 'Agent')
                            ->required(fn (Get $get) => $get('source') === 'Agent'),

                        Select::make('sales_id')
                            ->label('Sales Handle')
                            ->relationship('sales', 'full_name') 
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()->employee?->id)
                            ->required(),

                        TextInput::make('potential_package')
                            ->label('Minat Paket')
                            ->placeholder('Misal: Paket Hemat Februari'),

                        ToggleButtons::make('status')
                            ->label('Status Prospek')
                            ->options([
                                'cold' => 'Cold (Baru Tanya)',
                                'warm' => 'Warm (Minta Info)',
                                'hot' => 'Hot (Nego/Siap Bayar)',
                                'closing' => 'Closing (Jadi Booking)',
                                'lost' => 'Lost (Batal)',
                            ])
                            ->colors([
                                'cold' => 'info',
                                'warm' => 'warning',
                                'hot' => 'danger',
                                'closing' => 'success',
                                'lost' => 'gray',
                            ])
                            ->icons([
                                'cold' => 'heroicon-m-hand-raised',
                                'warm' => 'heroicon-m-sun',
                                'hot' => 'heroicon-m-fire',
                                'closing' => 'heroicon-m-check-badge',
                                'lost' => 'heroicon-m-x-circle',
                            ])
                            ->inline()
                            ->default('cold')
                            ->required(),
                        
                        Textarea::make('notes')
                            ->label('Catatan Follow Up')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama Calon')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Lead $record) => $record->potential_package),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cold' => 'info',
                        'warm' => 'warning',
                        'hot' => 'danger',
                        'closing' => 'success',
                        'lost' => 'gray',
                        'converted' => 'success',
                    }),

                TextColumn::make('sales.full_name')
                    ->label('Sales')
                    ->sortable(),
                
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('status'),
                SelectFilter::make('sales_id')
                    ->label('Sales')
                    ->relationship('sales', 'full_name'),
            ])
            ->recordActions([
                EditAction::make()->label(''),
                Action::make('whatsapp')
                    ->label('Follow Up')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (Lead $record) => in_array($record->status, ['cold', 'warm','hot', 'closing']) && $record->status !== 'converted')
                    ->url(function (Lead $record) {
                        $phone = $record->phone;
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        }
                        
                        $text = "Assalamu'alaikum Kak {$record->name}, saya dari Rawabi Travel. Mau menanyakan perihal rencana umrahnya...";
                        
                        return "https://wa.me/{$phone}?text=" . urlencode($text);
                    }, shouldOpenInNewTab: true),

                Action::make('convert')
                    ->label('Convert to Jamaah')
                    ->icon('heroicon-m-user-plus')
                    ->color('primary')
                    ->visible(fn (Lead $record) => in_array($record->status, ['hot', 'closing']) && $record->status !== 'converted')
                    ->requiresConfirmation()
                    ->modalWidth('2xl')
                    ->modalHeading('Konversi & Booking')
                    ->modalDescription('Lengkapi data di bawah ini untuk mendaftarkan sebagai Jamaah Resmi.')
                    ->modalSubmitActionLabel('Lanjut Booking')                    
                    ->form([
                        ToggleButtons::make('mode')
                            ->label('Status Data Jamaah')
                            ->options([
                                'new' => 'Jamaah Baru (Register)',
                                'existing' => 'Sudah Pernah Daftar (RO)',
                            ])
                            ->colors([
                                'new' => 'success',
                                'existing' => 'info',
                            ])
                            ->icons([
                                'new' => 'heroicon-m-user-plus',
                                'existing' => 'heroicon-m-magnifying-glass',
                            ])
                            ->default('new')
                            ->inline()
                            ->reactive() 
                            ->afterStateUpdated(fn ($state, Set $set) => 
                                $state === 'existing' ? $set('email', null) : null
                            ),

                        Section::make('Cari Data Jamaah')
                            ->visible(fn (Get $get) => $get('mode') === 'existing')
                            ->schema([
                                Select::make('existing_jamaah_id')
                                    ->label('Cari Nama / NIK / No HP')
                                    ->options(Jamaah::query()
                                        
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($item) => [$item->id => "{$item->name} - {$item->nik}"])
                                    )
                                    ->searchable()
                                    ->getSearchResultsUsing(fn (string $search) => Jamaah::where('name', 'like', "%{$search}%")
                                        ->orWhere('nik', 'like', "%{$search}%")
                                        ->orWhere('phone_number', 'like', "%{$search}%")
                                        ->limit(50)
                                        ->pluck('name', 'id'))
                                    ->required(fn (Get $get) => $get('mode') === 'existing')
                                    ->preload(),
                            ]),

                        Section::make('Registrasi Jamaah Baru')
                            ->visible(fn (Get $get) => $get('mode') === 'new')
                            ->schema([
                                Section::make('Akun Aplikasi')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('email')
                                                ->label('Email Jamaah')
                                                ->email()
                                                ->required(fn (Get $get) => $get('mode') === 'new')
                                                ->rules([
                                                        'unique:users,email', 
                                                    ]),

                                            TextInput::make('password')
                                                ->label('Password')
                                                ->password()
                                                ->revealable()
                                                ->required(fn (Get $get) => $get('mode') === 'new'),
                                        ])
                                    ])->compact(),

                                Section::make('Data Diri')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('nik')
                                                ->label('NIK KTP')
                                                ->numeric()
                                                ->minLength(16)
                                                ->maxLength(16)
                                                ->required(fn (Get $get) => $get('mode') === 'new'),
                                            
                                            TextInput::make('name')
                                                ->label('Nama Lengkap')
                                                ->required(fn (Get $get) => $get('mode') === 'new')
                                                ->default(fn ($record) => $record->name),
                                            
                                            Select::make('gender')
                                                ->label('Jenis Kelamin')
                                                ->options([
                                                    'pria' => 'Pria',
                                                    'wanita' => 'Wanita',
                                                ])
                                                ->required(fn (Get $get) => $get('mode') === 'new'),
                                            
                                            TextInput::make('phone')
                                                ->label('No. WhatsApp')
                                                ->prefix('+62') 
                                                ->tel()
                                                ->required(fn (Get $get) => $get('mode') === 'new')
                                                ->default(fn ($record) => $record->phone),
                                            
                                            Textarea::make('address')
                                                ->label('Alamat')
                                                ->default(fn ($record) => $record->city)
                                                ->columnSpanFull(),
                                        ])
                                    ])->compact(),
                                Section::make('Data Dokumen')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('passport_number')
                                                ->label('Nomor Paspor'),
                                                
                                            DatePicker::make('passport_expiry')
                                                ->label('Tgl Kadaluarsa Paspor'),
                                                
                                            TextInput::make('shirt_size')
                                                ->label('Ukuran Baju (S/M/L/XL/XXL)'),
                                        ])
                                        
                                    ])->compact(),
                            ]),
                    ])

                    ->action(function (Lead $record, array $data) {
                        $jamaahId = null;

                        if ($data['mode'] === 'existing') {
                            $jamaahId = $data['existing_jamaah_id'];
                            
                            $record->update(['status' => 'converted']);
                            
                            return redirect()->to(BookingResource::getUrl('create', [
                                'jamaah_id' => $jamaahId,
                            ]));
                        }

                        $newJamaah = DB::transaction(function () use ($record, $data) {
                            
                            $user = User::create([
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'password' => Hash::make($data['password']),
                                'is_active' => true,
                            ]);
                            $user->assignRole('jamaah'); 

                            unset($data['mode']);
                            unset($data['existing_jamaah_id']);
                            unset($data['email']);
                            unset($data['password']);

                            $data['user_id'] = $user->id;
                            $data['agent_id'] = $record->agent_id;
                            $data['status'] = 'active';

                            $jamaah = Jamaah::create($data);

                            $record->update(['status' => 'converted']);

                            return $jamaah;
                        });

                        return redirect()->to(BookingResource::getUrl('create', [
                            'jamaah_id' => $newJamaah->id,
                            'sales_id' => $record->sales_id,
                            'agent_id' => $record->agent_id,
                        ]));
                    }),
                
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
            'index' => ManageLeads::route('/'),
        ];
    }
}

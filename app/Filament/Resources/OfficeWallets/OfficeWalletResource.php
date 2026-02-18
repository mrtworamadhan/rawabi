<?php

namespace App\Filament\Resources\OfficeWallets;

use App\Filament\Resources\OfficeWallets\Pages\ManageOfficeWallets;
use App\Models\CashTransaction;
use App\Models\OfficeWallet;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class OfficeWalletResource extends Resource
{
    protected static ?string $model = OfficeWallet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string | UnitEnum | null $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Kas & PettyCash';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kas / Dompet')
                    ->required(),
                
                TextInput::make('balance')
                    ->label('Saldo Awal')
                    ->numeric()
                    ->prefix('IDR')
                    ->disabledOn('edit')
                    ->default(0),
                
                Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'petty_cash' => 'Petty Cash',
                        'cashier' => 'Kas Kasir',
                        'bank' => 'Rekening Bank',
                    ])
                    ->required(),

                TextInput::make('balance')
                    ->label('Saldo Awal')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->helperText('Update saldo ini akan berubah otomatis saat ada transaksi masuk/keluar.'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                
                TextColumn::make('balance')
                    ->label('Sisa Saldo')
                    ->money('IDR')
                    ->weight('bold')
                    ->color(fn (string $state): string => $state < 1000000 ? 'danger' : 'success'),

                TextColumn::make('updated_at')->date()->label('Update Terakhir'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('top_up')
                    ->label('Top Up Dana')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        DatePicker::make('transaction_date')
                            ->default(now())
                            ->required(),
                        TextInput::make('amount')
                            ->label('Nominal Masuk')
                            ->numeric()
                            ->prefix('IDR')
                            ->required(),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->default('Tambahan Modal Operasional dari Perusahaan')
                            ->required(),
                    ])
                    ->action(function (OfficeWallet $record, array $data) {
                        CashTransaction::create([
                            'office_wallet_id' => $record->id,
                            'type' => 'deposit',
                            'amount' => $data['amount'],
                            'transaction_date' => $data['transaction_date'],
                            'description' => $data['description'],
                            'user_id' => Auth::id(),
                        ]);
                        
                        Notification::make()
                            ->title('Saldo Berhasil Ditambahkan')
                            ->success()
                            ->send();
                    }),
                Action::make('history')
                    ->label('Mutasi Terakhir')
                    ->icon('heroicon-m-clock')
                    ->modalHeading('10 Transaksi Terakhir')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Tutup'))
                    
                    ->infolist(function (Schema $infolist) {
                        return $infolist
                            ->schema([
                                RepeatableEntry::make('transactions')
                                    ->label('') 
                                    ->getStateUsing(fn (OfficeWallet $record) => $record->transactions()->latest()->limit(10)->get())
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('transaction_date')
                                                    ->label('Tanggal')
                                                    ->date('d M Y')
                                                    ->icon('heroicon-m-calendar'),

                                                TextEntry::make('description')
                                                    ->label('Keterangan')
                                                    ->color(fn ($record) => $record->type === 'deposit' ? 'success' : 'danger'),
                                                
                                                TextEntry::make('type')
                                                    ->color(fn ($record) => $record->type === 'deposit' ? 'success' : 'danger'),
                                                
                                                TextEntry::make('amount')
                                                    ->label('Nominal')
                                                    ->money('IDR')
                                                    ->weight('bold')
                                                    ->color(fn ($record) => $record->type === 'deposit' ? 'success' : 'danger')
                                                    ->prefix(fn ($record) => $record->type === 'deposit' ? '+ ' : '- '),
                                            ]),
                                    ])
                                    ->contained(true)
                            ]);
                    })
            ])
            ->headerActions([

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOfficeWallets::route('/'),
        ];
    }
}

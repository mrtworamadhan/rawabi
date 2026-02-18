<?php

namespace App\Filament\Resources\BankAccounts;

use App\Filament\Resources\BankAccounts\Pages\ManageBankAccounts;
use App\Models\BankAccount;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Rekening Bank';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bank_name')
                    ->label('Nama Bank')
                    ->placeholder('Contoh: Bank BSI')
                    ->required(),
                
                TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->numeric()
                    ->required(),
                    
                TextInput::make('account_holder')
                    ->label('Atas Nama')
                    ->default('PT Rawabi Zamzam')
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
                TextColumn::make('bank_name')->weight('bold'),
                TextColumn::make('account_number')->copyable(),
                TextColumn::make('account_holder'),
                TextColumn::make('balance')
                    ->money('IDR')
                    ->color('success')
                    ->weight('bold'),
                ToggleColumn::make('is_active'),
            ])
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
            'index' => ManageBankAccounts::route('/'),
        ];
    }
}

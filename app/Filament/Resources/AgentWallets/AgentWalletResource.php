<?php

namespace App\Filament\Resources\AgentWallets;

use App\Filament\Resources\AgentWallets\Pages\ManageAgentWallets;
use App\Models\AgentTransaction;
use App\Models\AgentWallet;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgentWalletResource extends Resource
{
    protected static ?string $model = AgentWallet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string | UnitEnum | null $navigationGroup = 'Manajemen Agen';
    protected static ?string $navigationLabel = 'Komisi Agen';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('agent_id')
                    ->relationship('agent', 'name')
                    ->required(),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('agent.name')
                    ->label('Agent'),
                TextEntry::make('balance')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('agent.name')
                    ->label('Nama Agen')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->agent->phone),

                TextColumn::make('balance')
                    ->label('Saldo Komisi')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Transaksi')
                    ->date('d M Y H:i'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make('history')
                ->label('Riwayat')
                ->modalHeading('Riwayat Transaksi Agen')
                ->infolist(fn (Schema $infolist) => $infolist
                    ->components([
                        RepeatableEntry::make('transactions')
                            ->label('')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('created_at')->date('d M Y'),
                                    TextEntry::make('amount')
                                        ->money('IDR')
                                        ->color(fn ($record) => $record->type === 'in' ? 'success' : 'danger')
                                        ->prefix(fn ($record) => $record->type === 'in' ? '+' : '-'),
                                    TextEntry::make('description')->columnSpan(2),
                                ])
                            ])
                    ])
                ),
                Action::make('withdraw')
                    ->label('Cairkan Dana')
                    ->icon('heroicon-m-banknotes')
                    ->color('danger')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal Pencairan')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->maxValue(fn ($record) => $record->balance),
                        
                        FileUpload::make('proof_file')
                            ->label('Bukti Transfer Bank')
                            ->directory('disbursements')
                            ->image()
                            ->required(),
                            
                        Textarea::make('description')
                            ->label('Catatan')
                            ->default('Pencairan Komisi Agen'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->decrement('balance', $data['amount']);

                        AgentTransaction::create([
                            'agent_wallet_id' => $record->id,
                            'type' => 'out',
                            'amount' => $data['amount'],
                            'reference_type' => 'withdrawal',
                            'proof_file' => $data['proof_file'],
                            'description' => $data['description'],
                        ]);

                        $category = ExpenseCategory::where('name', 'Komisi Agen')->first();
                        if (!$category) {
                            $category = ExpenseCategory::where('type', 'operational')->first();
                        }

                        Expense::create([
                            'expense_category_id' => $category->id,
                            'transaction_date' => now(),
                            'name' => 'Pencairan Komisi: ' . $record->agent->name,
                            'amount' => $data['amount'],
                            'proof_file' => $data['proof_file'],
                            'status' => 'approved',
                            'approved_by' => auth()->user()->employee?->id, 
                            'note' => $data['description'],
                            'office_wallet_id' => null, 
                            'bank_account_id' => $data['bank_account_id'], 
                        ]);

                        $bank = BankAccount::find($data['bank_account_id']);
                        if ($bank) {
                            $bank->decrement('balance', $data['amount']);
                        }
                        Notification::make()
                            ->title('Pencairan Berhasil Dicatat')
                            ->success()
                            ->send();
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
            'index' => ManageAgentWallets::route('/'),
        ];
    }
}

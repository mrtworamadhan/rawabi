<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Catatan Pembayaran';


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('created_at')
                    ->label('Tanggal Bayar')
                    ->default(now())
                    ->required(),

                TextInput::make('amount')
                    ->label('Jumlah Bayar')
                    ->numeric()
                    ->prefix('IDR')
                    ->required(),

                Select::make('type')
                    ->options([
                        'dp' => 'Down Payment (DP)',
                        'pelunasan' => 'Pelunasan',
                        'cicilan' => 'Cicilan',
                    ])
                    ->required(),

                Select::make('method')
                    ->label('Metode Bayar')
                    ->options([
                        'transfer' => 'Transfer Bank',
                        'cash' => 'Tunai / Cash',
                    ])
                    ->required(),

                FileUpload::make('proof_file')
                    ->label('Bukti Transfer')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('payments') 
                    ->openable(),
                
                Hidden::make('verified_at')
                    ->default(now()),

                Hidden::make('verified_by')
                    ->default(fn () => Auth::user()->employee?->id),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y'),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('amount')
                    ->money('IDR'),
                TextColumn::make('method')
                    ->label('Via'),
                ImageColumn::make('proof_file')
                    ->label('Bukti')
                    ->height(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Input Pembayaran'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('print_kwitansi')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => '#')
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

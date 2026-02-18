<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Pengeluaran')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('office_wallet_id')
                                ->label('Sumber Dana (Kas)')
                                ->relationship('wallet', 'name')
                                ->required()
                                ->helperText('Saldo akan berkurang dari kas yang dipilih.'),
                            DatePicker::make('transaction_date')
                                ->label('Tanggal Transaksi')
                                ->default(now())
                                ->required(),

                            Select::make('expense_category_id')
                                ->label('Kategori')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    TextInput::make('name')->required(),
                                    Select::make('type')
                                        ->options(['operational'=>'Operasional', 'hpp'=>'HPP (Produksi)'])
                                        ->required()
                                ]), 

                            TextInput::make('name')
                                ->label('Keterangan')
                                ->placeholder('Contoh: Beli Kertas A4 2 Rim')
                                ->required(),

                            TextInput::make('amount')
                                ->label('Nominal (Rp)')
                                ->numeric()
                                ->prefix('IDR')
                                ->required(),

                            FileUpload::make('proof_file')
                                ->label('Bukti Nota / Bon')
                                ->directory('expenses')
                                ->image()
                                ->openable(),
                                
                            Hidden::make('status')
                                ->default('approved'),
                                
                            Hidden::make('approved_by')
                                ->default(fn () => auth()->user()->employee?->id),
                        ])
                        
                    ])->columnSpanFull(),

            ]);
    }
}

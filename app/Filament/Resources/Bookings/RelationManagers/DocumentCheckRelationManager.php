<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentCheckRelationManager extends RelationManager
{
    protected static string $relationship = 'documentCheck';

    protected static ?string $title = 'Kelengkapan Dokumen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dokumen Pribadi')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('ktp')
                                ->label('KTP Asli'),
                            Toggle::make('kk')
                                ->label('Kartu Keluarga'),
                            Toggle::make('buku_nikah')
                                ->label('Buku Nikah'),
                            Toggle::make('vaccine_cert')
                                ->label('Kartu Vaksin Meningitis'),                                     
                        ])
                        
                    ])->columnSpanFull(),

                Section::make('Dokumen Perjalanan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('passport_status')
                                ->options([
                                    'missing' => 'Belum Ada',
                                    'on_process' => 'Sedang Diproses',
                                    'received' => 'Sudah Diterima Travel',
                                ])
                                ->default('missing')
                                ->required(),

                            Select::make('visa_status')
                                ->options([
                                    'pending' => 'Belum Request',
                                    'requested' => 'Sedang Request Muassasah',
                                    'issued' => 'Visa Terbit (Issued)',
                                ])
                                ->default('pending')
                                ->required(),
                            
                            FileUpload::make('visa_file')
                                ->label('File E-Visa')
                                ->directory('jamaah-documents/visas')
                                ->disk('public')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->openable(),
                        ])
                        
                    ])->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                IconColumn::make('ktp')->boolean()->label('KTP'),
                IconColumn::make('passport_status')
                    ->label('Paspor')
                    ->icon(fn (string $state): string => match ($state) {
                        'missing' => 'heroicon-o-x-circle',
                        'on_process' => 'heroicon-o-clock',
                        'received' => 'heroicon-o-check-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'missing' => 'danger',
                        'on_process' => 'warning',
                        'received' => 'success',
                    }),
                TextColumn::make('visa_status')
                    ->label('Status Visa')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'requested' => 'info',
                        'issued' => 'success',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Buat Checklist')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->documentCheck === null),
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
}

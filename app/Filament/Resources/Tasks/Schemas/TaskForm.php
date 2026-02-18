<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Tugas')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Tugas')
                            ->required()
                            ->disabled(fn ($record) => $record && !auth()->user()->hasRole('super_admin')),

                        DatePicker::make('due_date')
                            ->label('Tenggat Waktu')
                            ->required()
                            ->disabled(fn ($record) => $record && !auth()->user()->hasRole('super_admin')),
                        
                        Select::make('priority')
                            ->options([
                                1 => 'Normal',
                                2 => 'High',
                                3 => 'URGENT',
                            ])
                            ->default(1)
                            ->disabled(fn ($record) => $record && !auth()->user()->hasRole('super_admin')),

                        Select::make('employee_id')
                            ->label('Tugaskan Ke')
                            ->relationship('assignee', 'full_name')
                            ->visible(auth()->user()->hasRole('super_admin'))
                            ->required(),

                        Hidden::make('employee_id')
                            ->default(auth()->user()->employee?->id)
                            ->visible(!auth()->user()->hasRole('super_admin')),

                        Hidden::make('assigned_by')
                            ->default(auth()->user()->employee?->id),
                            
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->disabled(fn ($record) => $record && !auth()->user()->hasRole('super_admin')),
                    ])->columns(2),

                Section::make('Laporan Penyelesaian')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Belum Dikerjakan',
                                'in_progress' => 'Sedang Proses',
                                'completed' => 'Selesai',
                            ])
                            ->default('pending')
                            ->required()
                            ->reactive(),

                        Textarea::make('completion_note')
                            ->label('Catatan Pengerjaan')
                            ->placeholder('Contoh: Sudah ditelepon, hasilnya...')
                            ->visible(fn ($get) => $get('status') === 'completed' || $get('status') === 'in_progress'),

                        FileUpload::make('proof_file')
                            ->label('Bukti Foto / Dokumen')
                            ->directory('tasks-proof')
                            ->openable()
                            ->visible(fn ($get) => $get('status') === 'completed'),
                    ])->columns(2),
            ]);
    }
}

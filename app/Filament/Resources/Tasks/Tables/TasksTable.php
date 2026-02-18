<?php

namespace App\Filament\Resources\Tasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Tugas')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('assignee.full_name')
                    ->label('PIC')
                    ->visible(auth()->user()->hasRole('super_admin')),

                TextColumn::make('due_date')
                    ->date('d M')
                    ->label('Deadline')
                    ->color(fn ($record) => $record->due_date < now() && $record->status !== 'completed' ? 'danger' : 'gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'overdue' => 'danger',
                    }),
                
                IconColumn::make('proof_file')
                    ->label('Bukti')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-x-mark'),
            ])
            ->filters([
                SelectFilter::make('status'),
                SelectFilter::make('employee_id')
                    ->label('Karyawan')
                    ->relationship('assignee', 'full_name')
                    ->visible(auth()->user()->hasRole('super_admin')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

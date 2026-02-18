<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nik_karyawan')
                    ->label('NIK')
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Employee $record) => $record->position),

                TextColumn::make('user.email')
                    ->label('Akun Login')
                    ->icon('heroicon-o-envelope')
                    ->color('gray'),

                TextColumn::make('departmentRel.name')
                    ->label('Divisi')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'probation' => 'warning',
                        'contract' => 'info',
                        'permanent' => 'success',
                        'resign' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'probation' => 'Masa Percobaan (Probation)',
                        'contract' => 'Kontrak',
                        'permanent' => 'Tetap',
                        'resign' => 'Non-Aktif / Resign',
                        default => $state,
                    })
                    ->sortable(),
                
                TextColumn::make('phone_number')
                    ->label('Kontak'),
            ])
            ->filters([
                SelectFilter::make('department'),
                SelectFilter::make('status'),
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

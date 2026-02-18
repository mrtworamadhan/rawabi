<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipe Akun')
                    ->badge()
                    ->getStateUsing(function (User $record) {
                        if ($record->employee) return 'Karyawan';
                        if ($record->hasRole('super_admin')) return 'Super Admin';
                        return 'User Biasa';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Karyawan' => 'info',
                        'Jamaah' => 'success',
                        'Super Admin' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color('warning'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name'),
                    
                TernaryFilter::make('is_active')
                    ->label('Status Akun'),
            ])
            ->recordActions([
                EditAction::make()->label(''),
                DeleteAction::make()->label('')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

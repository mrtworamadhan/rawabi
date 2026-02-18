<?php

namespace App\Filament\Resources\Attendances\Tables;

use App\Models\Attendance;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('clock_in_photo')
                    ->label('Selfie')
                    ->circular(),
                
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('date')->date('d M Y')->sortable(),
                
                TextColumn::make('clock_in_time')->time('H:i')->label('Masuk'),
                TextColumn::make('clock_out_time')->time('H:i')->label('Pulang'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'on_time' => 'success',
                        'late' => 'danger',
                        'permit' => 'warning',
                    }),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('date', now())),
            ])
            ->recordActions([
                Action::make('clock_out')
                    ->label('Absen Pulang')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Attendance $record) => $record->update(['clock_out_time' => now()]))
                    ->visible(fn (Attendance $record) => $record->clock_out_time === null && $record->user_id === auth()->id()),
                
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\MarketingReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MarketingReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->date('d M Y')->sortable(),
            
                TextColumn::make('employee.full_name')
                    ->label('Sales')
                    ->visible(fn () => auth()->user()->hasRole('super_admin')) 
                    ->sortable(),

                TextColumn::make('activity_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'closing' => 'success',
                        'meeting' => 'info',
                        'canvasing' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('location_name')
                    ->limit(20),
                TextColumn::make('prospect_qty')
                    ->label('Prospek'),
            ])
            ->defaultSort('date', 'desc') 
            ->filters([
                SelectFilter::make('activity_type'),
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->visible(fn () => auth()->id() != 1),
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

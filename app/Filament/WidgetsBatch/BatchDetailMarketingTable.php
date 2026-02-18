<?php

namespace App\Filament\WidgetsBatch;

use App\Models\Employee;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\Url;

class BatchDetailMarketingTable extends BaseWidget
{
    protected static ?string $heading = 'Kontribusi Marketing';

    #[Url(as: 'record', keep: true)]
    public $recordId = null;


    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->when($this->recordId, function ($query) {
                        $query->whereHas('salesBookings', fn ($q) => $q->where('umrah_package_id', $this->recordId))
                        
                              ->withCount(['salesBookings' => fn ($q) => $q->where('umrah_package_id', $this->recordId)])
                        
                              ->orderByDesc('sales_bookings_count');
                    })
                    
                    ->when(!$this->recordId, fn($q) => $q->whereRaw('1 = 0'))
            )
            ->searchable(false)
            ->striped()
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nama Marketing')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('sales_bookings_count')
                    ->label('Total Jamaah')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->paginated(false);
    }
}
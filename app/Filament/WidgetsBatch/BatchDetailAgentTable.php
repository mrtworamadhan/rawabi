<?php

namespace App\Filament\WidgetsBatch;

use App\Models\Agent;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\Url; // WAJIB: Jangan lupa import ini

class BatchDetailAgentTable extends BaseWidget
{
    protected static ?string $heading = 'Kontribusi Agen';

    #[Url(as: 'record', keep: true)]
    public $recordId = null;


    public function table(Table $table): Table
    {
        return $table
            ->query(
                Agent::query()
                    ->when($this->recordId, function ($query) {
                        $query->whereHas('bookings', fn ($q) => $q->where('umrah_package_id', $this->recordId))
                        
                              ->withCount(['bookings' => fn ($q) => $q->where('umrah_package_id', $this->recordId)])
                              
                              ->orderByDesc('bookings_count');
                    })
                    
                    ->when(!$this->recordId, fn($q) => $q->whereRaw('1 = 0'))
            )
            ->searchable(false)
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Agen')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('bookings_count')
                    ->label('Total Jamaah')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->paginated(false); 
    }
}
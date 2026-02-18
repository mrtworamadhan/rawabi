<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Page;
use BackedEnum;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;


class TeamMonitor extends Page implements HasTable, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithTable;
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;
    protected static ?string $navigationLabel = 'Monitoring Kinerja';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.team-monitor';

    public function table(Table $table): Table
    {
        return $table
            ->query(Employee::query()->where('status', '!=', 'resign'))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Employee $record) => $record->position),

                TextColumn::make('departmentRel.name')
                    ->label('Departemen')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('tasks_today')
                    ->label('Progres Hari Ini')
                    ->state(function (Employee $record) {
                        $total = Task::where('employee_id', $record->id)
                            ->whereDate('created_at', today())
                            ->count();
                        
                        $done = Task::where('employee_id', $record->id)
                            ->whereDate('created_at', today())
                            ->where('status', 'completed')
                            ->count();

                        if ($total === 0) return 'Tidak Ada Tugas';
                        
                        return "{$done} / {$total} Selesai";
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state === 'Tidak Ada Tugas') return 'gray';
                        if (str_contains($state, '/')) {
                            [$done, $total] = explode(' / ', str_replace(' Selesai', '', $state));
                            if ($done == $total) return 'success';
                            if ($done == 0) return 'danger';
                            return 'warning';
                        }
                        return 'gray';
                    }),

                TextColumn::make('monthly_performance')
                    ->label('Performa Bulan Ini')
                    ->state(function (Employee $record) {
                        $tasks = Task::where('employee_id', $record->id)
                            ->whereMonth('created_at', now()->month)
                            ->get();
                        
                        if ($tasks->count() === 0) return '-';

                        $completed = $tasks->where('status', 'completed')->count();
                        $percentage = round(($completed / $tasks->count()) * 100);

                        return "{$percentage}%";
                    })
                    ->icon(fn ($state) => $state === '100%' ? 'heroicon-m-trophy' : null)
                    ->color(fn ($state) => (int)$state >= 80 ? 'success' : ((int)$state >= 50 ? 'warning' : 'danger')),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Filter Departemen')
                    ->relationship('departmentRel', 'name'),
            ])
            ->actions([
                ViewAction::make('view_tasks')
                    ->label('Lihat Detail')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(fn ($record) => "Tugas Hari Ini: " . $record->full_name)
                    ->modalWidth('2xl')
                    // Logic Infolist di dalam Modal
                    ->infolist(fn (Schema $infolist) => $infolist
                        ->schema([
                            Section::make('Daftar Tugas')
                                ->columnSpanFull()
                                ->description('List tugas yang untuk hari ini.')
                                ->schema([
                                    RepeatableEntry::make('todaysTasks')
                                        ->label('Daftar Tugas')
                                        ->schema([
                                            Grid::make(3)->schema([
                                                
                                                TextEntry::make('title')
                                                    ->label('Tugas')
                                                    ->weight(FontWeight::Bold)
                                                    ->columnSpan(3),
                                                
                                                TextEntry::make('template.frequency')
                                                    ->label('Tipe')
                                                    ->badge()
                                                    ->columnSpan(1)
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'daily' => 'success',
                                                        'weekly' => 'warning',
                                                        'monthly' => 'info',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('status')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'completed' => 'success',
                                                        'pending' => 'danger',
                                                        'in_progress' => 'warning',
                                                        default => 'gray',
                                                    })
                                                    ->columnSpan(1)
                                                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                                                
                                                TextEntry::make('due_date')
                                                    ->label('Deadline')
                                                    ->dateTime('M j, Y H:i') 
                                                    ->columnSpan(1)
                                                    ->icon('heroicon-m-clock')
                                                    ->color(fn ($record) => $record->due_date < now() && $record->status !== 'completed' ? 'danger' : 'gray'),
                                                
                                                TextEntry::make('completion_note')
                                                    ->label('Catatan')
                                                    ->placeholder('-')
                                                    ->columnSpan(3)
                                                    ->visible(fn ($record) => $record->status === 'completed'),
                                            ])
                                        ])
                                ])
                        ])
                    )
            ]);
    }
}

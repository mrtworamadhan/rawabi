<?php

namespace App\Filament\WidgetsWorkspace;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Components\Tabs;
use Illuminate\Database\Eloquent\Builder;

class MyDailyTasks extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Daftar Tugas Saya';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where('employee_id', auth()->user()->employee?->id)
                    ->whereIn('status', ['pending', 'in_progress']) 
                    ->orderBy('due_date', 'asc') 
            )
            ->searchable(false)
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Tugas')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Task $record) => $record->description)
                    ->wrap(),

                TextColumn::make('template.frequency')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'success' => 'daily',
                        'warning' => 'weekly',
                        'info' => 'monthly',
                        'danger' => 'incidental',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        'incidental' => 'Insidentil',
                        default => $state,
                    }),

                TextColumn::make('due_date')
                    ->label('Deadline')
                    ->date('d M, H:i') 
                    ->badge()
                    ->color(fn ($record) => $record->due_date < now() ? 'danger' : 'gray'),

                // TextColumn::make('priority')
                //     ->label('Prioritas')
                //     ->badge()
                //     ->color(fn ($state) => match($state) {
                //         3 => 'danger',
                //         2 => 'warning',
                //         1 => 'info',
                //         default => 'gray',
                //     })
                //     ->formatStateUsing(fn ($state) => match($state) {
                //         3 => 'URGENT',
                //         2 => 'High',
                //         1 => 'Normal',
                //         default => 'Low',
                //     }),
            ])
            ->filters([
                SelectFilter::make('frequency')
                    ->label('Filter Tipe Tugas')
                    ->options([
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        'incidental' => 'Insidentil',
                    ])
                    ->query(fn (Builder $query, array $data) => 
                        $query->when($data['value'], fn ($q) => 
                            $q->whereHas('template', fn($qt) => $qt->where('frequency', $data['value']))
                        )
                    )
            ])
            ->actions([
                Action::make('work')
                    ->label('Kerjakan')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('primary')
                    ->button()
                    ->url(fn (Task $record) => $record->template->action_url) 
                    ->openUrlInNewTab() 
                    ->visible(fn (Task $record) => 
                        !empty($record->template->action_url) && 
                        $record->status !== 'completed' &&
                        $record->due_date > now() // Cek Deadline
                    ),
                    
                Action::make('mark_done')
                    ->label('Lapor Selesai')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->button()
                    ->visible(fn (Task $record) => 
                            $record->status === 'pending' &&
                            $record->due_date > now()
                        )
                    ->form([
                        FileUpload::make('proof_file')
                            ->label('Bukti Foto / Screenshot')
                            ->image()
                            ->directory('task-proofs')
                            ->required(),
                        
                        Textarea::make('notes')
                            ->label('Catatan Pengerjaan')
                            ->placeholder('Contoh: Sudah follow up 10 orang, 2 orang minta meeting.')
                            ->required(),
                    ])
                    ->action(function (Task $record, array $data) {
                        $record->update(attributes: [
                            'status' => 'completed',
                            'proof_file' => $data['proof_file'],
                            'notes' => $data['notes'],
                            'completed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Tugas Selesai!')
                            ->success()
                            ->send();
                    }),
                ViewAction::make('view_proof')
                    ->label('Lihat Laporan')
                    ->color('gray')
                    ->visible(fn (Task $record) => $record->status === 'completed')
                    ->form([
                        FileUpload::make('proof_file')
                            ->label('Bukti')
                            ->image()
                            ->disabled(),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->disabled(),
                    ]),

                Action::make('expired_alert')
                    ->label('Anda melewatkan tugas ini')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('danger') 
                    ->disabled()
                    ->visible(fn (Task $record) => 
                        $record->status === 'pending' && 
                        $record->due_date <= now() 
                    ),
            ])
            ->paginated(false);
    }
}
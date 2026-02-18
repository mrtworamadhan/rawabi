<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskTemplate;
use Carbon\Carbon;

class GenerateDailyTasks extends Command
{
    protected $signature = 'tasks:generate-daily';

    protected $description = 'Generate tugas harian untuk semua karyawan aktif berdasarkan template';

    public function handle()
    {
        $this->info('--- MEMULAI DEBUG GENERATOR TUGAS ---');

        $employees = Employee::where('status', '!=', 'resign')
            ->whereNotNull('department_id')
            ->get();

        $count = 0;

        foreach ($employees as $employee) {
            $this->line("");
            $this->info("Processing Karyawan: {$employee->full_name}");

            $templates = TaskTemplate::where('is_active', true)
                ->where('department_id', $employee->department_id)
                ->get();

            foreach ($templates as $template) {
                $shouldCreate = false;

                if ($template->frequency === 'daily') {
                    $exists = Task::where('employee_id', $employee->id)->where('task_template_id', $template->id)->whereDate('created_at', today())->exists();
                    $shouldCreate = !$exists;
                } elseif ($template->frequency === 'weekly') {
                    $exists = Task::where('employee_id', $employee->id)->where('task_template_id', $template->id)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->exists();
                    $shouldCreate = !$exists;
                } elseif ($template->frequency === 'monthly') {
                    $exists = Task::where('employee_id', $employee->id)->where('task_template_id', $template->id)->whereMonth('created_at', now()->month)->exists();
                    $shouldCreate = !$exists;
                }

                if ($shouldCreate) {
                    $baseDate = now();
                    
                    if ($template->frequency === 'weekly') {
                        $baseDate = now()->endOfWeek()->subDays(2); 
                    } elseif ($template->frequency === 'monthly') {
                        $baseDate = now()->endOfMonth();
                    }

                    $dueDate = $baseDate->copy();

                    if (!empty($template->deadline_time)) {
                        try {
                            $parsedTime = \Carbon\Carbon::parse($template->deadline_time);
                            
                            $dueDate = $dueDate->setTime(
                                $parsedTime->hour, 
                                $parsedTime->minute, 
                                0
                            );
                            
                        } catch (\Exception $e) {
                            $dueDate = $dueDate->endOfDay();
                            $this->error("   > Error Parse Time: {$template->deadline_time}");
                        }
                    } else {
                        $dueDate = $dueDate->endOfDay();
                    }

                    Task::create([
                        'employee_id' => $employee->id,
                        'task_template_id' => $template->id,
                        'title' => $template->title,
                        'description' => $template->description,
                        'status' => 'pending',
                        'due_date' => $dueDate,
                        'priority' => 1,
                    ]);
                    
                    $count++;
                }
            }
        }

        $this->info("Selesai. Total task dibuat: {$count}");
    }
}
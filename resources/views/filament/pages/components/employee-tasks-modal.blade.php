@php
    $tasks = \App\Models\Task::where('employee_id', $employee->id)
        ->whereDate('created_at', now())
        ->get();
@endphp

<div class="space-y-4">
    @if($tasks->isEmpty())
        <div class="text-center text-gray-500 py-4">
            Belum ada tugas yang digenerate hari ini.
        </div>
    @else
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($tasks as $task)
                <li class="py-3 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        @if($task->status === 'completed')
                            <x-heroicon-s-check-circle class="w-6 h-6 text-success-500"/>
                        @else
                            <x-heroicon-o-clock class="w-6 h-6 text-gray-400"/>
                        @endif
                        
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $task->title }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ ucfirst($task->template->frequency ?? 'daily') }} 
                                @if($task->completed_at)
                                    | Selesai: {{ $task->completed_at->format('H:i') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $task->status === 'completed' ? 'bg-success-100 text-success-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($task->status) }}
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
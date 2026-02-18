<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use App\Models\ContentSchedule;
use App\Models\MediaAsset;
use App\Models\ContentRequest;
use App\Models\UmrahPackage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

new #[Layout('layouts::operations')] class extends Component
{
    use WithFileUploads;

    public $activeTab = 'calendar';
    public $viewMode = 'calendar';
    public $currentMonth;
    public $currentYear;

    public $upload_tags_input;
    public $sched_links = [];

    public $photos = [];
    public $upload_package_id;

    public $showScheduleModal = false;
    public $sched_id, $sched_title, $sched_date, $sched_caption;
    public $sched_status = 'idea';
    public $sched_platforms = [];

    public $filter_asset_type = 'all';
    public $filter_asset_package = 'all';
    
    public $showRequestModal = false;
    public $editReqId;
    public $editReqTitle;
    public $editReqDesc;
    public $editReqStatus;
    public $editReqResultFile;
    public $editReqExistingResult;

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    public function setTab($tab) { $this->activeTab = $tab; }

    // --- DATA ---
    public function getSchedulesProperty()
    {
        return ContentSchedule::query()
            ->whereMonth('scheduled_date', $this->currentMonth)
            ->whereYear('scheduled_date', $this->currentYear)
            ->orderBy('scheduled_date')
            ->get();
    }

    public function getAssetsProperty()
    {
        return MediaAsset::query()
            ->when($this->filter_asset_type !== 'all', function($q) {
                $q->where('file_type', $this->filter_asset_type);
            })
            ->when($this->filter_asset_package !== 'all', function($q) {
                if ($this->filter_asset_package === 'general') {
                    $q->whereNull('umrah_package_id');
                } else {
                    $q->where('umrah_package_id', $this->filter_asset_package);
                }
            })
            ->latest()
            ->paginate(24);
    }

    public function getRequestsProperty()
    {
        return ContentRequest::with('requester')
            ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'review', 'done')")
            ->orderBy('deadline')
            ->get();
    }

    public function getPackagesProperty()
    {
        return UmrahPackage::orderBy('departure_date', 'desc')->take(10)->get();
    }

    // --- ACTION: UPLOAD MASS---
    public function updatedPhotos()
    {
        $this->validate([
            'photos.*' => 'image|max:10240', 
        ]);

        $tagsArray = null;
        if ($this->upload_tags_input) {
            $tagsArray = array_filter(array_map('trim', explode(',', $this->upload_tags_input)));
        }

        foreach ($this->photos as $photo) {
            $path = $photo->store('media-assets', 'public');

            MediaAsset::create([
                'file_path' => $path,
                'file_type' => 'image',
                'umrah_package_id' => $this->upload_package_id,
                'tags' => $tagsArray,
                'uploaded_by' => Auth::id(),
                'title' => $photo->getClientOriginalName(),
            ]);
        }

        $this->reset('photos'); 
        
        Notification::make()->title('Upload Berhasil')->success()->send();
    }

    // --- ACTION: JADWAL KONTEN ---
    
    // Buka Modal 
    public function openScheduleModal($date = null)
    {
        $this->reset(['sched_id', 'sched_title', 'sched_caption', 'sched_platforms', 'sched_links']);
        $this->sched_status = 'idea';
        $this->sched_date = $date ?? now()->format('Y-m-d');
        $this->showScheduleModal = true;
    }

    // Buka Modal (Edit Existing)
    public function editSchedule($id)
    {
        $sched = ContentSchedule::find($id);
        $this->sched_id = $sched->id;
        $this->sched_title = $sched->title;
        $this->sched_date = $sched->scheduled_date->format('Y-m-d');
        $this->sched_status = $sched->status;
        $this->sched_caption = $sched->caption_draft;
        $this->sched_platforms = $sched->platforms ?? [];
        $this->sched_links = json_decode($sched->attachment_path, true) ?? [];

        $this->showScheduleModal = true;
    }

    // Simpan Jadwal
    public function saveSchedule()
    {
        $this->validate([
            'sched_title' => 'required',
            'sched_date' => 'required|date',
            'sched_platforms' => 'required|array|min:1',
            'sched_links.*' => $this->sched_status === 'published' ? 'required|url' : 'nullable',
        ]);

        $data = [
            'title' => $this->sched_title,
            'scheduled_date' => $this->sched_date,
            'status' => $this->sched_status,
            'caption_draft' => $this->sched_caption,
            'platforms' => $this->sched_platforms,
            'attachment_path' => !empty($this->sched_links) ? json_encode($this->sched_links) : null,
        ];

        if ($this->sched_id) {
            ContentSchedule::find($this->sched_id)->update($data);
            Notification::make()->title('Jadwal Diupdate')->success()->send();
        } else {
            ContentSchedule::create($data);
            Notification::make()->title('Jadwal Dibuat')->success()->send();
        }

        $this->showScheduleModal = false;
    }

    // Navigation
    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function prevMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function openRequestModal($id)
    {
        $req = ContentRequest::find($id);
        $this->editReqId = $req->id;
        $this->editReqTitle = $req->title;
        $this->editReqDesc = $req->description;
        $this->editReqStatus = $req->status;
        $this->editReqExistingResult = $req->result_path;
        $this->reset('editReqResultFile');
        
        $this->showRequestModal = true;
    }

    // 2. ACTION: UPDATE REQUEST (SIMPAN HASIL)
    public function updateRequest()
    {
        $this->validate([
            'editReqStatus' => 'required',
            'editReqResultFile' => 'nullable|file|max:20480',
        ]);

        $req = ContentRequest::find($this->editReqId);
        
        $data = [
            'status' => $this->editReqStatus,
        ];

        if ($this->editReqResultFile) {
            $path = $this->editReqResultFile->store('request-results', 'public');
            $data['result_path'] = $path;
        }

        $req->update($data);
        
        Notification::make()->title('Request Diupdate')->success()->send();
        $this->showRequestModal = false;
    }
};
?>

@section('header')
    <div class="w-8 h-8 bg-black dark:bg-white rounded-lg flex items-center justify-center text-white dark:text-black font-black text-sm">
        M
    </div>
    <div class="flex flex-col">
        <span class="font-bold text-lg leading-none">MEDIA COMMAND CENTER</span>
        <span class="text-[10px] text-gray-500 dark:text-zinc-500 tracking-wider">Content, Assets & Design</span>
    </div>
@endsection

<div class="flex w-full h-full relative" 
     x-data="{ 
         showScheduleModal: @entangle('showScheduleModal'),
         showRequestModal: @entangle('showRequestModal')  }">

    <aside class="hidden md:flex w-24 bg-white dark:bg-zinc-900 border-r border-gray-200 dark:border-white/10 flex-col items-center py-6 gap-6 z-20 shadow-sm shrink-0 h-screen sticky top-0">

        <button
            wire:click="setTab('calendar')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'calendar'
                ? 'text-cyan-600 bg-cyan-50 dark:bg-cyan-400/10 dark:text-cyan-400 font-bold ring-1 ring-cyan-500/20'
                : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            <x-heroicon-o-calendar class="w-6 h-6 transition-transform group-hover:scale-110" />
            <span class="text-[9px] uppercase font-bold tracking-wide">CALENDAR</span>
        </button>

        <button
            wire:click="setTab('assets')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'assets'
                ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-400/10 dark:text-indigo-400 font-bold ring-1 ring-indigo-500/20'
                : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            <x-heroicon-o-photo class="w-6 h-6 transition-transform group-hover:scale-110" />
            <span class="text-[9px] uppercase font-bold tracking-wide">ASSETS</span>
        </button>

        <button
            wire:click="setTab('requests')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'requests'
                ? 'text-rose-600 bg-rose-50 dark:bg-rose-400/10 dark:text-rose-400 font-bold ring-1 ring-rose-500/20'
                : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            
            <div class="relative">
                <x-heroicon-o-clipboard-document-list class="w-6 h-6 transition-transform group-hover:scale-110" />
                
                @if($this->requests && $this->requests->where('status', 'pending')->count() > 0)
                    <span class="absolute -top-1 -right-2 flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500 text-[9px] text-white justify-center items-center">
                            {{ $this->requests->where('status', 'pending')->count() }}
                        </span>
                    </span>
                @endif
            </div>

            <span class="text-[9px] uppercase font-bold tracking-wide">REQUEST</span>
        </button>

        <div class="flex-1"></div>

        <button title="Filter Tim" class="mb-4 text-gray-400 hover:text-gray-600 dark:hover:text-zinc-300">
            <x-heroicon-o-funnel class="w-6 h-6" />
        </button>

    </aside>

    <nav class="md:hidden fixed bottom-0 w-full bg-white dark:bg-zinc-900 border-t border-gray-200 dark:border-white/10 flex justify-around items-end pb-4 pt-2 z-40 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
    
        <button wire:click="setTab('calendar')" 
            class="flex flex-col items-center gap-1 w-16 transition {{ $activeTab === 'calendar' ? 'text-cyan-600 dark:text-cyan-400' : 'text-gray-400 dark:text-zinc-500' }}">
            <x-heroicon-o-calendar class="w-6 h-6" />
            <span class="text-[10px] font-bold">CALENDAR</span>
        </button>

        <button wire:click="setTab('assets')" 
            class="flex flex-col items-center gap-1 w-16 transition {{ $activeTab === 'assets' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-zinc-500' }}">
            <x-heroicon-o-photo class="w-6 h-6" />
            <span class="text-[10px] font-bold">ASSETS</span>
        </button>

        <button wire:click="setTab('requests')" 
            class="relative flex flex-col items-center gap-1 w-16 transition {{ $activeTab === 'requests' ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400 dark:text-zinc-500' }}">
            
            <div class="relative">
                <x-heroicon-o-clipboard-document-list class="w-6 h-6" />
                @if($this->requests && $this->requests->where('status', 'pending')->count() > 0)
                    <span class="absolute -top-1 -right-1 block h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-white dark:ring-zinc-900"></span>
                @endif
            </div>
            
            <span class="text-[10px] font-bold">REQUEST</span>
        </button>

    </nav>

    <div class="flex-1 flex flex-col h-full min-w-0 bg-gray-50 dark:bg-zinc-950 overflow-hidden relative">
        
        <header class="bg-white dark:bg-zinc-900 border-b border-gray-200 dark:border-white/5 px-6 py-4 flex justify-between items-center shrink-0">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    @if($activeTab === 'calendar')
                        <x-heroicon-o-calendar-days class="w-6 h-6 text-emerald-600" />
                        <span>Jadwal Konten</span>
                    @elseif($activeTab === 'assets')
                        <x-heroicon-o-photo class="w-6 h-6 text-emerald-600" />
                        <span>Asset Bank</span>
                    @else
                        <x-heroicon-o-pencil-square class="w-6 h-6 text-emerald-600" />
                        <span>Request Desain</span>
                    @endif
                </h2>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Creative Studio Management</p>
            </div>

            <div class="flex items-center gap-3">
                @if($activeTab === 'calendar')
                    <button
                        wire:click="openScheduleModal"
                        class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700
                            text-white px-3 py-2 rounded-lg text-sm font-bold
                            transition shadow-sm"
                    >
                        <x-heroicon-m-plus class="w-4 h-4" />
                        <span>Tambah</span>
                    </button>

                    
                    <div class="flex bg-gray-100 dark:bg-zinc-800 p-1 rounded-lg">
                        <button wire:click="$set('viewMode', 'calendar')" class="p-2 rounded-md transition {{ $viewMode == 'calendar' ? 'bg-white dark:bg-zinc-700 shadow text-emerald-600' : 'text-gray-400' }}">
                            <x-heroicon-m-calendar class="w-5 h-5" />
                        </button>
                        <button wire:click="$set('viewMode', 'list')" class="p-2 rounded-md transition {{ $viewMode == 'list' ? 'bg-white dark:bg-zinc-700 shadow text-emerald-600' : 'text-gray-400' }}">
                            <x-heroicon-m-list-bullet class="w-5 h-5" />
                        </button>
                    </div>
                @elseif($activeTab === 'requests')
                    @endif
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">

            @if($activeTab === 'calendar')
                <div class="flex justify-between items-center mb-6 bg-white dark:bg-zinc-900 p-3 rounded-xl border border-gray-200 dark:border-white/5 shadow-sm">
                    <button wire:click="prevMonth" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-lg transition text-gray-600 dark:text-gray-300">
                        <x-heroicon-m-chevron-left class="w-5 h-5"/>
                    </button>
                    <span class="font-bold text-lg text-gray-800 dark:text-white min-w-[140px] text-center">
                        {{ Carbon::create($currentYear, $currentMonth)->format('F Y') }}
                    </span>
                    <button wire:click="nextMonth" class="p-2 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-lg transition text-gray-600 dark:text-gray-300">
                        <x-heroicon-m-chevron-right class="w-5 h-5"/>
                    </button>
                </div>

                @if($viewMode === 'calendar')
                    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
                        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-white/5 bg-gray-50 dark:bg-zinc-800/50">
                            @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $day)
                                <div class="py-3 text-center text-xs font-bold text-gray-400 uppercase">{{ $day }}</div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-7 auto-rows-fr">
                            @php
                                $date = Carbon::create($currentYear, $currentMonth, 1);
                                $startDay = $date->dayOfWeekIso; 
                                $daysInMonth = $date->daysInMonth;
                                $events = $this->schedules->groupBy(fn($item) => $item->scheduled_date->format('j'));
                            @endphp

                            @for($i = 1; $i < $startDay; $i++)
                                <div class="min-h-[120px] border-b border-r border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-zinc-950"></div>
                            @endfor

                            @for($day = 1; $day <= $daysInMonth; $day++)
                                <div wire:click.self="openScheduleModal('{{ $currentYear }}-{{ $currentMonth }}-{{ $day }}')" 
                                     class="min-h-[120px] border-b border-r border-gray-100 dark:border-white/5 p-2 relative hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition group cursor-pointer">
                                    
                                    <span class="text-sm font-bold {{ $day == now()->day && $currentMonth == now()->month ? 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-0.5 rounded-full' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $day }}
                                    </span>

                                    @if(isset($events[$day]))
                                        <div class="mt-2 space-y-1">
                                            @foreach($events[$day] as $content)
                                                <button wire:click.stop="editSchedule({{ $content->id }})" 
                                                    class="w-full text-left text-[10px] p-1.5 rounded border truncate hover:scale-[1.02] transition block
                                                    {{ match($content->status) {
                                                        'published' => 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:border-green-800',
                                                        'ready' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800',
                                                        default => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-zinc-800 dark:text-white dark:border-zinc-200'
                                                    } }}">
                                                    {{ $content->title }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    <button 
                                        wire:click.stop="openScheduleModal('{{ $currentYear }}-{{ $currentMonth }}-{{ $day }}')"
                                        class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 text-emerald-500 hover:text-emerald-700 transition-all hover:scale-110"
                                        title="Tambah Jadwal Baru"
                                    >
                                        <x-heroicon-m-plus-circle class="w-6 h-6" />
                                    </button>
                                </div>
                            @endfor
                        </div>
                    </div>
                @else
                    <div class="space-y-4 max-w-3xl mx-auto">
                        @foreach($this->schedules as $schedule)
                            <div wire:click="editSchedule({{ $schedule->id }})" class="flex gap-4 group cursor-pointer">
                                <div class="w-16 text-center pt-2">
                                    <span class="block text-xl font-bold text-gray-800 dark:text-white">{{ $schedule->scheduled_date->format('d') }}</span>
                                    <span class="block text-xs text-gray-500 uppercase">{{ $schedule->scheduled_date->format('M') }}</span>
                                </div>
                                <div class="flex-1 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-gray-200 dark:border-white/5 shadow-sm group-hover:border-emerald-500 transition">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-bold text-gray-800 dark:text-white">{{ $schedule->title }}</h4>
                                        <span class="text-[10px] px-2 py-1 rounded-full uppercase font-bold
                                            {{ match($schedule->status) {
                                                'published' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                                'ready' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                default => 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-zinc-400'
                                            } }}">
                                            {{ $schedule->status }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $schedule->caption_draft ?? '...' }}</p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        @php
                                            $links = [];
                                            if ($schedule->status === 'published' && !empty($schedule->attachment_path)) {
                                                $links = json_decode($schedule->attachment_path, true) ?? [];
                                            }
                                        @endphp

                                        @foreach($schedule->platforms ?? [] as $plat)
                                            @php
                                                $url = $links[$plat] ?? null;
                                                $isClickable = $schedule->status === 'published' && !empty($url);
                                            @endphp

                                            @if($isClickable)
                                                <a href="{{ $url }}" target="_blank" @click.stop 
                                                class="flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-lg border transition shadow-sm hover:scale-105 group
                                                {{ match($plat) {
                                                    'instagram' => 'bg-pink-50 text-pink-700 border-pink-200 hover:bg-pink-100 dark:bg-pink-900/20 dark:text-pink-400 dark:border-pink-800',
                                                    'tiktok'    => 'bg-zinc-800 text-white border-zinc-900 hover:bg-black dark:bg-black dark:border-zinc-700',
                                                    'facebook'  => 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
                                                    default     => 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200'
                                                } }}">
                                                    
                                                    @if($plat == 'instagram') <span class="text-pink-500 dark:text-pink-400">📸</span>
                                                    @elseif($plat == 'tiktok') <span class="text-white">🎵</span>
                                                    @elseif($plat == 'facebook') <span class="text-blue-600 dark:text-blue-400">f</span>
                                                    @endif

                                                    {{ ucfirst($plat) }}
                                                    
                                                    <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3 opacity-50 group-hover:opacity-100" />
                                                </a>
                                            @else
                                                <span class="flex items-center gap-1 text-xs bg-gray-100 dark:bg-zinc-800 border border-transparent px-2 py-1 rounded text-gray-500 dark:text-zinc-500 cursor-default opacity-80">
                                                    @if($plat == 'instagram')
                                                    @elseif($plat == 'tiktok')
                                                    @elseif($plat == 'facebook')
                                                    @endif
                                                    {{ ucfirst($plat) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($this->schedules->isEmpty())
                            <div class="text-center p-10 text-gray-400 italic">Belum ada jadwal konten bulan ini.</div>
                        @endif
                    </div>
                @endif
            @endif

            @if($activeTab === 'assets')
            <div class="space-y-6">
                
                <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-gray-200 dark:border-white/5 space-y-3 shadow-sm">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                        <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                        Setting Upload Baru
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <select wire:model="upload_package_id" class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-sm p-2.5 dark:text-white focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">-- Upload ke Folder Umum --</option>
                                @foreach($this->packages as $pkg)
                                    <option value="{{ $pkg->id }}">{{ $pkg->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <input wire:model="upload_tags_input" type="text" placeholder="Tags: promo, manasik, testimoni..." 
                                class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-sm p-2.5 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 placeholder:text-gray-400">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border-2 border-dashed border-gray-300 dark:border-zinc-700 text-center hover:border-emerald-500 transition relative group cursor-pointer bg-gray-50/50 dark:bg-zinc-800/30">
                    <input type="file" wire:model="photos" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    
                    <div class="space-y-2 pointer-events-none">
                        <div wire:loading wire:target="photos">
                            <x-heroicon-o-arrow-path class="w-10 h-10 mx-auto text-emerald-500 animate-spin" />
                            <p class="text-emerald-600 font-bold text-sm">Mengupload...</p>
                        </div>
                        <div wire:loading.remove wire:target="photos">
                            <div class="w-12 h-12 bg-white dark:bg-zinc-700 shadow-sm rounded-full flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                                <x-heroicon-o-cloud-arrow-up class="w-6 h-6 text-emerald-500" />
                            </div>
                            <h3 class="font-bold text-gray-800 dark:text-white text-sm">Tap untuk Upload Massal</h3>
                            <p class="text-xs text-gray-500">
                                Foto/Video akan masuk ke: 
                                <span class="font-bold text-emerald-600">{{ $upload_package_id ? 'Grup Terpilih' : 'Umum' }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-4 border-t border-gray-200 dark:border-white/5">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-photo class="w-4 h-4 text-emerald-500" />
                        Galeri Aset
                    </h3>

                    <div class="flex gap-2 w-full sm:w-auto overflow-x-auto pb-1 flex items-center">
                        <select wire:model.live="filter_asset_type" class="rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-xs py-1.5 px-3 dark:text-white focus:ring-emerald-500">
                            <option value="all">Semua Tipe</option>
                            <option value="image">Hanya Foto</option>
                            <option value="video">Hanya Video</option>
                        </select>

                        <select wire:model.live="filter_asset_package" class="rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-xs py-1.5 px-3 dark:text-white focus:ring-emerald-500 max-w-[150px]">
                            <option value="all">Semua Sumber</option>
                            <option value="general">Folder Umum</option>
                            @foreach($this->packages as $pkg)
                                <option value="{{ $pkg->id }}">{{ \Illuminate\Support\Str::limit($pkg->name, 20) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                    @foreach($this->assets as $asset)
                    <div class="group relative aspect-square bg-gray-100 dark:bg-zinc-800 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition border border-gray-200 dark:border-white/5">
                        
                        @if($asset->file_type == 'image')
                            <img src="{{ asset('storage/'.$asset->file_path) }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center bg-zinc-800 text-zinc-500 group-hover:text-zinc-300 transition">
                                <x-heroicon-o-video-camera class="w-8 h-8 mb-1" />
                                <span class="text-[10px] font-bold uppercase tracking-wider">Video</span>
                            </div>
                        @endif
                        
                        <div class="absolute top-2 left-2 flex flex-wrap gap-1 max-w-[80%]">
                            @if($asset->umrah_package_id)
                                <span class="bg-blue-500/80 backdrop-blur text-white text-[8px] px-1.5 py-0.5 rounded shadow-sm">
                                    Grup
                                </span>
                            @endif
                            @if($asset->tags)
                                @foreach(array_slice($asset->tags, 0, 2) as $tag) 
                                    <span class="bg-black/50 backdrop-blur text-white text-[8px] px-1.5 py-0.5 rounded shadow-sm">{{ $tag }}</span>
                                @endforeach
                            @endif
                        </div>

                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition duration-200 flex flex-col justify-end p-3">
                            <p class="text-white text-xs font-bold truncate mb-2">{{ $asset->title }}</p>
                            
                            <div class="flex items-center gap-2">
                                <a href="{{ asset('storage/'.$asset->file_path) }}" download class="flex-1 bg-white hover:bg-emerald-50 text-emerald-700 text-[10px] py-1.5 rounded-lg font-bold text-center shadow-sm flex items-center justify-center gap-1 transition">
                                    <x-heroicon-m-arrow-down-tray class="w-3 h-3" />
                                    <span>Unduh</span>
                                </a>

                                @if($asset->uploaded_by == auth()->id())
                                <button wire:click="deleteAsset({{ $asset->id }})" wire:confirm="Hapus file ini?" class="p-1.5 bg-red-500/80 hover:bg-red-600 text-white rounded-lg transition">
                                    <x-heroicon-m-trash class="w-3 h-3" />
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="py-4">
                    {{ $this->assets->links() }}
                </div>

                @if($this->assets->isEmpty())
                <div class="text-center py-12 border-2 border-dashed border-gray-100 dark:border-zinc-800 rounded-xl">
                    <x-heroicon-o-photo class="w-12 h-12 mx-auto text-gray-300 dark:text-zinc-600 mb-2" />
                    <p class="text-gray-400 dark:text-zinc-500 text-sm">Belum ada aset media sesuai filter ini.</p>
                </div>
                @endif
            </div>
            @endif

            @if($activeTab === 'requests')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->requests as $req)
                <div wire:click="openRequestModal({{ $req->id }})" 
                    class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-white/5 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 transition relative overflow-hidden cursor-pointer group">
                    
                    <div class="absolute top-0 left-0 w-1 h-full {{ $req->status_color }}"></div>
                    
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $req->priority_color }}">
                            {{ $req->priority }} Priority
                        </span>
                        <span class="text-xs text-gray-400">{{ $req->created_at->format('d M') }}</span>
                    </div>

                    <h4 class="font-bold text-gray-800 dark:text-white text-lg mb-2 group-hover:text-indigo-600 transition">{{ $req->title }}</h4>
                    <p class="text-sm text-gray-500 dark:text-zinc-400 line-clamp-2 mb-4">{{ $req->description }}</p>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-white/5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-bold text-gray-600">
                                {{ substr($req->requester->name, 0, 1) }}
                            </div>
                            <span class="text-xs text-gray-500">{{ $req->requester->name }}</span>
                        </div>
                        <span class="text-xs font-bold uppercase {{ explode(' ', $req->status_color)[1] ?? 'text-gray-500' }}">
                            {{ str_replace('_', ' ', $req->status) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

        </div>
    </div>

    <div x-show="showScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" style="display: none;" x-transition.opacity>
        <div @click="showScheduleModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="relative bg-white dark:bg-zinc-900 rounded-xl shadow-2xl w-full max-w-md p-6 overflow-y-auto max-h-[90vh]" x-transition.scale>
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100 dark:border-zinc-800">
                <h3 class="text-lg font-bold dark:text-white">{{ $sched_id ? 'Edit Jadwal' : 'Buat Jadwal Baru' }}</h3>
                <button @click="showScheduleModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Judul Konten</label>
                    <input wire:model="sched_title" type="text" class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-2.5 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Tanggal</label>
                        <input wire:model="sched_date" type="date" class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-2.5">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">
                            Status
                        </label>

                        <div class="flex items-center gap-2">
                            @if($sched_status === 'idea')
                                <x-heroicon-o-light-bulb class="w-5 h-5 text-yellow-500" />
                            @elseif($sched_status === 'drafting')
                                <x-heroicon-o-pencil-square class="w-5 h-5 text-blue-500" />
                            @elseif($sched_status === 'ready')
                                <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-500" />
                            @else
                                <x-heroicon-o-rocket-launch class="w-5 h-5 text-purple-500" />
                            @endif

                            <select
                                wire:model="sched_status"
                                class="w-full rounded-lg border-gray-300
                                    dark:bg-zinc-800 dark:border-zinc-700 dark:text-white
                                    p-2.5"
                            >
                                <option value="idea">Idea</option>
                                <option value="drafting">Drafting</option>
                                <option value="ready">Ready</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-2 block">Platform</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="sched_platforms" value="instagram" class="rounded text-emerald-600 focus:ring-emerald-500 bg-gray-100 dark:bg-zinc-800 border-gray-300 dark:border-zinc-700">
                            <span class="text-sm dark:text-white">Instagram</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="sched_platforms" value="tiktok" class="rounded text-emerald-600 focus:ring-emerald-500 bg-gray-100 dark:bg-zinc-800 border-gray-300 dark:border-zinc-700">
                            <span class="text-sm dark:text-white">TikTok</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="sched_platforms" value="facebook" class="rounded text-emerald-600 focus:ring-emerald-500 bg-gray-100 dark:bg-zinc-800 border-gray-300 dark:border-zinc-700">
                            <span class="text-sm dark:text-white">Facebook</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Draft Caption</label>
                    <textarea wire:model="sched_caption" rows="4" class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-2.5 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                </div>

                <div x-show="$wire.sched_status === 'published'" x-transition class="bg-green-50 dark:bg-green-900/10 p-4 rounded-xl border border-green-100 dark:border-green-800/30 space-y-3">
                    <p class="text-xs font-bold text-green-700 dark:text-green-400 uppercase flex items-center gap-2">
                        <x-heroicon-m-link class="w-4 h-4" /> Link Postingan (Wajib)
                    </p>

                    @if(empty($sched_platforms))
                        <p class="text-xs text-gray-400 italic">Pilih platform dulu di atas.</p>
                    @else
                        @foreach($sched_platforms as $plat)
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">
                                Link {{ ucfirst($plat) }}
                            </label>
                            <input wire:model="sched_links.{{ $plat }}" type="url" placeholder="https://..." 
                                class="w-full rounded-lg border-green-200 dark:bg-zinc-800 dark:border-green-900 dark:text-white p-2 text-sm focus:ring-green-500 focus:border-green-500">
                            @error("sched_links.$plat") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                <button @click="showScheduleModal = false" class="px-4 py-2 bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-300 rounded-lg font-bold hover:bg-gray-200 hover:text-red-500 transition">Batal</button>
                <button wire:click="saveSchedule" class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-500/30 transition">Simpan Jadwal</button>
            </div>
        </div>
    </div>

    <div x-show="showRequestModal" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" style="display: none;" x-transition.opacity>
        <div @click="showRequestModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="relative bg-white dark:bg-zinc-900 rounded-xl shadow-2xl w-full max-w-lg p-6 overflow-y-auto max-h-[90vh]" x-transition.scale>
            
            <div class="flex justify-between items-start mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">
                <div>
                    <h3 class="text-lg font-bold dark:text-white">Proses Request</h3>
                    <p class="text-sm text-gray-500">Update status & upload hasil pengerjaan.</p>
                </div>
                <button @click="showRequestModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </button>
            </div>

            <div class="space-y-5">
                <div class="bg-gray-50 dark:bg-zinc-800/50 p-4 rounded-lg border border-gray-100 dark:border-zinc-800">
                    <h4 class="font-bold text-gray-800 dark:text-white mb-1">{{ $editReqTitle }}</h4>
                    <p class="text-sm text-gray-600 dark:text-zinc-400 mb-3">{{ $editReqDesc }}</p>
                    <div class="flex gap-2">
                        <span class="text-xs bg-white dark:bg-zinc-700 border px-2 py-1 rounded text-gray-500">
                            Deadline: {{ $this->requests->find($editReqId)?->deadline?->format('d M Y') ?? '-' }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Update Status</label>
                    <select wire:model="editReqStatus" class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-2.5">
                        <option value="pending">Pending (Menunggu)</option>
                        <option value="in_progress">In Progress (Sedang Dikerjakan)</option>
                        <option value="review">In Review (Revisi/Cek)</option>
                        <option value="done">Done (Selesai ✅)</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Upload Hasil (Gambar/Zip/Video)</label>
                    
                    <div class="border-2 border-dashed border-gray-300 dark:border-zinc-700 rounded-xl p-4 text-center hover:border-indigo-500 transition relative group">
                        <input type="file" wire:model="editReqResultFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        
                        <div class="space-y-1">
                            <div wire:loading wire:target="editReqResultFile">
                                <x-heroicon-o-arrow-path class="w-8 h-8 mx-auto text-indigo-500 animate-spin" />
                                <p class="text-xs text-indigo-600 font-bold">Uploading...</p>
                            </div>
                            <div wire:loading.remove wire:target="editReqResultFile">
                                <x-heroicon-o-paper-clip class="w-8 h-8 mx-auto text-gray-400 group-hover:text-indigo-500" />
                                <p class="text-sm font-bold text-gray-600 dark:text-zinc-300">Pilih File Hasil</p>
                            </div>
                        </div>
                    </div>
                    @error('editReqResultFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                    @if($editReqExistingResult)
                        <div class="mt-2 flex items-center gap-2 bg-green-50 dark:bg-green-900/20 p-2 rounded border border-green-200 dark:border-green-800">
                            <x-heroicon-o-check-circle class="w-4 h-4 text-green-600" />
                            <a href="{{ asset('storage/'.$editReqExistingResult) }}" target="_blank" class="text-xs text-green-700 dark:text-green-400 underline truncate">
                                Lihat File Hasil Sebelumnya
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                <button @click="showRequestModal = false" class="px-4 py-2 bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-300 rounded-lg font-bold hover:bg-gray-200 hover:text-red-500 transition">Batal</button>
                <button wire:click="updateRequest" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-lg transition">Simpan Perubahan</button>
            </div>
        </div>
    </div>

</div>
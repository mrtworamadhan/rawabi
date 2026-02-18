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

new #[Layout('layouts::app')] class extends Component
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

        // Jika ada file baru diupload
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

<div class="flex flex-col h-full w-full relative bg-slate-50 dark:bg-[#09090b]" 
     x-data="{ 
        mobileMenuOpen: false,
        showScheduleModal: @entangle('showScheduleModal'),
        showRequestModal: @entangle('showRequestModal')
     }">
    <div class="absolute -bottom-24 -right-24 w-128 h-128 opacity-40 dark:opacity-40 pointer-events-none transform">
        <img src="{{ asset('images/icons/kabah1.png') }}" alt="Kabah Decoration" class="w-full h-full object-contain">
    </div>
    <nav class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md px-4 py-2.5 flex justify-between items-center border-b border-slate-200 dark:border-white/5 shrink-0 z-50 relative"
        style="
            background-image: url('/images/ornaments/arabesque.png');
            background-repeat: repeat;
            background-size: 150px 150px;
        ">
        
        <div class="flex items-center gap-4">

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-pink-600 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-pink-500/20">
                    <x-heroicon-s-swatch class="w-6 h-6" />
                </div>
                <div class="flex flex-col">
                    <span class="font-black text-sm md:text-base tracking-tight leading-none uppercase">
                        Creative <span class="text-pink-600 dark:text-pink-400">Studio</span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-zinc-500 tracking-widest uppercase">
                            Media Command Center
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 md:gap-4">
            <button @click="toggleTheme()" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800 transition">
                <x-heroicon-s-moon class="w-5 h-5" x-show="!darkMode" />
                <x-heroicon-s-sun class="w-6 h-6 text-yellow-500" x-show="darkMode" x-cloak />
            </button>

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center gap-2 p-1 pr-3 rounded-full bg-slate-100 dark:bg-zinc-800 hover:ring-2 hover:ring-pink-500/30 transition-all cursor-pointer">
                    <div class="h-7 w-7 rounded-full bg-pink-600 flex items-center justify-center text-white font-black text-[10px] shadow-sm">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <span class="text-xs font-bold hidden md:block">{{ explode(' ', auth()->user()->name ?? 'User')[0] }}</span>
                </button>

                <div x-show="open" 
                     @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     class="absolute right-0 mt-3 w-56 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-white/5 py-2 z-50 origin-top-right overflow-hidden"
                     style="display: none;"
                     x-cloak>
                    
                    <div class="px-4 py-3 bg-slate-50 dark:bg-white/5 mb-2">
                        <p class="text-xs font-black text-slate-900 dark:text-white truncate uppercase tracking-wider">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                    </div>

                    <a href="/admin" class="group flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-pink-600 dark:text-zinc-400 dark:hover:text-white transition-all">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-zinc-800 flex items-center justify-center group-hover:bg-pink-600 group-hover:text-white transition-all">
                            <x-heroicon-s-squares-2x2 class="w-4 h-4" />
                        </div>
                        Admin Panel
                    </a>

                    <div class="border-t border-slate-100 dark:border-white/5 my-1"></div>

                    <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                        @csrf
                        <button type="submit" class="w-full group flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                            <div class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-500/10 flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition-all">
                                <x-heroicon-s-arrow-right-on-rectangle class="w-4 h-4" />
                            </div>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex-1 flex overflow-hidden relative">

        <aside class="hidden md:flex w-24 bg-white dark:bg-zinc-900 border-r border-slate-200 dark:border-white/5 flex-col items-center py-8 gap-6 z-30 shadow-sm shrink-0">
            
            <button wire:click="setTab('calendar')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'calendar' ? 'text-cyan-600 dark:text-cyan-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'calendar' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-cyan-500/10' }}">
                    <x-heroicon-s-calendar-days class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Calendar</span>
                @if($activeTab === 'calendar') <div class="absolute -right-[25px] w-1.5 h-8 bg-cyan-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="setTab('assets')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'assets' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'assets' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-indigo-500/10' }}">
                    <x-heroicon-s-photo class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Assets</span>
                @if($activeTab === 'assets') <div class="absolute -right-[25px] w-1.5 h-8 bg-indigo-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="setTab('requests')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'requests' ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'requests' ? 'bg-rose-600 text-white shadow-lg shadow-rose-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-rose-500/10' }} relative">
                    <x-heroicon-s-clipboard-document-list class="w-6 h-6" />
                    
                    @if($this->requests && $this->requests->where('status', 'pending')->count() > 0)
                        <span class="absolute -top-1 -right-1 flex h-4 w-4">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500 text-[9px] text-white justify-center items-center font-bold border-2 border-white dark:border-zinc-900">
                                {{ $this->requests->where('status', 'pending')->count() }}
                            </span>
                        </span>
                    @endif
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Request</span>
                @if($activeTab === 'requests') <div class="absolute -right-[25px] w-1.5 h-8 bg-rose-600 rounded-l-full"></div> @endif
            </button>

        </aside>

        <div x-show="mobileMenuOpen" @click="mobileMenuOpen = false" x-transition.opacity class="fixed inset-0 bg-slate-900/80 z-50 md:hidden backdrop-blur-sm"></div>
        <div x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-[280px] bg-white dark:bg-zinc-900 shadow-2xl flex flex-col border-r border-slate-200 dark:border-white/10 md:hidden">
            
            <div class="flex justify-between items-center p-6 border-b border-slate-100 dark:border-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-600 to-rose-600 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-lg">M</div>
                    <div>
                        <span class="font-black text-lg text-slate-900 dark:text-white tracking-tight leading-none block">Media</span>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-500 uppercase tracking-widest font-bold">Command Center</span>
                    </div>
                </div>
                <button @click="mobileMenuOpen = false"><x-heroicon-m-x-mark class="w-6 h-6 text-slate-400" /></button>
            </div>

            <div class="flex-1 p-4 space-y-2">
                <button wire:click="setTab('calendar'); mobileMenuOpen = false" class="flex items-center gap-4 px-4 py-4 rounded-xl font-bold text-sm w-full text-left {{ $activeTab === 'calendar' ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-400 ring-1 ring-cyan-500/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-calendar-days class="w-6 h-6" /> <span>Calendar</span>
                </button>
                <button wire:click="setTab('assets'); mobileMenuOpen = false" class="flex items-center gap-4 px-4 py-4 rounded-xl font-bold text-sm w-full text-left {{ $activeTab === 'assets' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 ring-1 ring-indigo-500/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-photo class="w-6 h-6" /> <span>Assets Bank</span>
                </button>
                <button wire:click="setTab('requests'); mobileMenuOpen = false" class="flex items-center gap-4 px-4 py-4 rounded-xl font-bold text-sm w-full text-left {{ $activeTab === 'requests' ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 ring-1 ring-rose-500/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-clipboard-document-list class="w-6 h-6" /> <span>Design Requests</span>
                </button>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5">
                <p class="text-[10px] text-center text-slate-400 uppercase font-bold tracking-widest">
                    Rawabi System v1.0 <br>
                    <span class="opacity-50 font-normal">© {{ date('Y') }} All Rights Reserved</span>
                </p>
            </div>
        </div>

        <main class="flex-1 h-full overflow-y-auto custom-scrollbar px-4 md:px-8 pb-24 md:pb-8 pt-0 relative">
            
            <div class="mb-8 sticky top-0 bg-slate-50/90 dark:bg-zinc-950/90 backdrop-blur-md z-20 py-4 -mx-4 px-4 md:-mx-8 md:px-8 border-b border-transparent transition-all mt-4"
                 :class="{ 'border-slate-200 dark:border-white/5 shadow-sm !mt-0 !pt-4': $el.closest('main').scrollTop > 0 }">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="relative">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-2xl transition-colors shadow-sm
                                @if($activeTab == 'calendar') bg-cyan-500/10
                                @elseif($activeTab == 'assets') bg-indigo-500/10
                                @elseif($activeTab == 'requests') bg-rose-500/10
                                @endif
                                ">
                                
                                @if($activeTab == 'calendar') <x-heroicon-s-calendar-days class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
                                @elseif($activeTab == 'assets') <x-heroicon-s-photo class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                                @elseif($activeTab == 'requests') <x-heroicon-s-clipboard-document-list class="w-6 h-6 text-rose-600 dark:text-rose-400" />
                                @endif
                            </div>
                            <div>
                                <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                                    @if($activeTab == 'calendar') Content Schedule
                                    @elseif($activeTab == 'assets') Asset Management
                                    @elseif($activeTab == 'requests') Design Requests
                                    @endif
                                </h1>
                                <p class="text-[10px] md:text-xs text-slate-500 dark:text-zinc-500 font-bold uppercase tracking-widest flex items-center gap-1.5">
                                    Creative Studio <i class="bi bi-chevron-right text-[8px]"></i> 
                                    <span class="text-pink-600 dark:text-pink-400">Workspace</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        
                        @if($activeTab === 'calendar')
                            <div class="flex bg-white dark:bg-zinc-900 p-1 rounded-xl border border-slate-200 dark:border-white/10 shadow-sm">
                                <button wire:click="$set('viewMode', 'calendar')" class="p-2 rounded-lg transition {{ $viewMode == 'calendar' ? 'bg-slate-100 dark:bg-zinc-800 text-cyan-600 font-bold' : 'text-slate-400 hover:text-slate-600' }}">
                                    <x-heroicon-m-calendar class="w-5 h-5" />
                                </button>
                                <button wire:click="$set('viewMode', 'list')" class="p-2 rounded-lg transition {{ $viewMode == 'list' ? 'bg-slate-100 dark:bg-zinc-800 text-cyan-600 font-bold' : 'text-slate-400 hover:text-slate-600' }}">
                                    <x-heroicon-m-list-bullet class="w-5 h-5" />
                                </button>
                            </div>
                            
                            <button wire:click="openScheduleModal" class="flex items-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2.5 rounded-xl text-sm font-black transition shadow-lg shadow-cyan-500/30 active:scale-95">
                                <x-heroicon-m-plus class="w-5 h-5" />
                                <span class="hidden md:inline">Jadwal Baru</span>
                            </button>
                        @endif

                    </div>
                </div>
            </div>

            <div class="space-y-8">
                @if($activeTab === 'calendar')
                <div class="space-y-6 animate-fade-in">

                    <div class="flex justify-between items-center bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm">
                        <button wire:click="prevMonth" class="p-2 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-xl transition text-slate-500 dark:text-zinc-400">
                            <x-heroicon-m-chevron-left class="w-6 h-6"/>
                        </button>
                        
                        <div class="text-center">
                            <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">
                                {{ Carbon::create($currentYear, $currentMonth)->translatedFormat('F Y') }}
                            </h2>
                            <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest mt-1">Content Schedule</p>
                        </div>

                        <button wire:click="nextMonth" class="p-2 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-xl transition text-slate-500 dark:text-zinc-400">
                            <x-heroicon-m-chevron-right class="w-6 h-6"/>
                        </button>
                    </div>

                    @if($viewMode === 'calendar')
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-slate-200 dark:border-white/5 overflow-hidden">
                            
                            <div class="grid grid-cols-7 border-b border-slate-200 dark:border-white/5 bg-slate-50 dark:bg-zinc-950/50">
                                @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $day)
                                    <div class="py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $day }}</div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-7 auto-rows-fr bg-slate-200 dark:bg-zinc-800 gap-px border-b border-slate-200 dark:border-white/5">
                                @php
                                    $date = Carbon::create($currentYear, $currentMonth, 1);
                                    $startDay = $date->dayOfWeekIso; 
                                    $daysInMonth = $date->daysInMonth;
                                    $events = $this->schedules->groupBy(fn($item) => $item->scheduled_date->format('j'));
                                @endphp

                                @for($i = 1; $i < $startDay; $i++)
                                    <div class="min-h-[140px] bg-white dark:bg-zinc-900/50"></div>
                                @endfor

                                @for($day = 1; $day <= $daysInMonth; $day++)
                                    <div wire:click.self="openScheduleModal('{{ $currentYear }}-{{ $currentMonth }}-{{ $day }}')" 
                                        class="min-h-[140px] bg-white dark:bg-zinc-900 p-3 relative group hover:bg-slate-50 dark:hover:bg-zinc-800/80 transition cursor-pointer flex flex-col gap-2">
                                        
                                        <span class="text-sm font-bold w-8 h-8 flex items-center justify-center rounded-full 
                                            {{ $day == now()->day && $currentMonth == now()->month 
                                                ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30' 
                                                : 'text-slate-500 dark:text-zinc-400 group-hover:bg-slate-200 dark:group-hover:bg-white/10' }}">
                                            {{ $day }}
                                        </span>

                                        @if(isset($events[$day]))
                                            <div class="flex flex-col gap-1.5 overflow-y-auto max-h-[90px] custom-scrollbar pr-1">
                                                @foreach($events[$day] as $content)
                                                    <button wire:click.stop="editSchedule({{ $content->id }})" 
                                                        class="w-full text-left p-2 rounded-lg border text-[10px] font-bold truncate transition hover:scale-[1.02] shadow-sm flex items-center gap-1.5
                                                        {{ match($content->status) {
                                                            'published' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400',
                                                            'ready' => 'bg-cyan-50 text-cyan-700 border-cyan-100 dark:bg-cyan-500/10 dark:border-cyan-500/20 dark:text-cyan-400',
                                                            'drafting' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-400',
                                                            default => 'bg-slate-50 text-slate-600 border-slate-100 dark:bg-white/5 dark:border-white/10 dark:text-zinc-400'
                                                        } }}">
                                                        
                                                        <div class="w-1.5 h-1.5 rounded-full {{ match($content->status) {
                                                            'published' => 'bg-emerald-500',
                                                            'ready' => 'bg-cyan-500',
                                                            'drafting' => 'bg-amber-500',
                                                            default => 'bg-slate-400'
                                                        } }}"></div>
                                                        
                                                        <span class="truncate">{{ $content->title }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                        <button 
                                            wire:click.stop="openScheduleModal('{{ $currentYear }}-{{ $currentMonth }}-{{ $day }}')"
                                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1.5 bg-cyan-100 text-cyan-600 dark:bg-cyan-500/20 dark:text-cyan-400 rounded-lg hover:bg-cyan-200 transition-all transform hover:scale-110"
                                            title="Tambah Jadwal"
                                        >
                                            <x-heroicon-s-plus class="w-4 h-4" />
                                        </button>
                                    </div>
                                @endfor
                            </div>
                        </div>

                    @else
                        <div class="space-y-4 max-w-4xl mx-auto">
                            @forelse($this->schedules as $schedule)
                                <div wire:click="editSchedule({{ $schedule->id }})" 
                                    class="group flex gap-6 p-4 bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm hover:border-cyan-400 hover:shadow-md transition cursor-pointer relative overflow-hidden">
                                    
                                    <div class="w-20 flex flex-col items-center justify-center bg-slate-50 dark:bg-white/5 rounded-xl border border-slate-100 dark:border-white/5 group-hover:bg-cyan-50 dark:group-hover:bg-cyan-500/10 transition-colors">
                                        <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $schedule->scheduled_date->format('d') }}</span>
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $schedule->scheduled_date->format('M') }}</span>
                                    </div>

                                    <div class="flex-1 flex flex-col justify-center">
                                        <div class="flex justify-between items-start mb-1">
                                            <h4 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-cyan-600 transition">{{ $schedule->title }}</h4>
                                            
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] uppercase font-black tracking-wide
                                                {{ match($schedule->status) {
                                                    'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                                    'ready' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                                                    'drafting' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                    default => 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-zinc-400'
                                                } }}">
                                                {{ $schedule->status }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-sm text-slate-500 dark:text-zinc-400 line-clamp-1 mb-3">
                                            {{ $schedule->caption_draft ?? 'Belum ada caption...' }}
                                        </p>

                                        <div class="flex flex-wrap gap-2">
                                            @php
                                                $links = $schedule->status === 'published' && !empty($schedule->attachment_path) 
                                                    ? json_decode($schedule->attachment_path, true) ?? [] 
                                                    : [];
                                            @endphp

                                            @foreach($schedule->platforms ?? [] as $plat)
                                                @php
                                                    $url = $links[$plat] ?? null;
                                                    $isClickable = !empty($url);
                                                @endphp

                                                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border transition
                                                    {{ $isClickable 
                                                        ? 'bg-white dark:bg-zinc-800 border-slate-200 dark:border-white/10 text-slate-600 dark:text-zinc-300 hover:border-cyan-400' 
                                                        : 'bg-slate-50 dark:bg-white/5 border-transparent text-slate-400 cursor-default' 
                                                    }}">
                                                    
                                                    @if($plat == 'instagram') <span class="text-pink-500">📸</span>
                                                    @elseif($plat == 'tiktok') <span class="text-black dark:text-white">🎵</span>
                                                    @elseif($plat == 'facebook') <span class="text-blue-600">📘</span>
                                                    @endif
                                                    
                                                    {{ ucfirst($plat) }}

                                                    @if($isClickable)
                                                        <a href="{{ $url }}" target="_blank" @click.stop class="ml-1 hover:text-cyan-600">
                                                            <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3"/>
                                                        </a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity transform translate-x-4 group-hover:translate-x-0">
                                        <x-heroicon-s-pencil-square class="w-6 h-6 text-cyan-400" />
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 text-center border-2 border-dashed border-slate-200 dark:border-white/5 rounded-2xl">
                                    <div class="p-4 bg-slate-50 dark:bg-white/5 rounded-full mb-3">
                                        <x-heroicon-o-calendar-days class="w-8 h-8 text-slate-300 dark:text-zinc-600" />
                                    </div>
                                    <p class="text-slate-400 dark:text-zinc-500 font-medium">Belum ada jadwal konten bulan ini.</p>
                                    <button wire:click="openScheduleModal" class="mt-4 text-xs font-bold text-cyan-600 hover:underline uppercase tracking-widest">
                                        + Buat Jadwal Baru
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    @endif

                </div>
                @endif

                @if($activeTab === 'assets')
                <div class="space-y-8 animate-fade-in">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm h-full flex flex-col justify-center">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2 mb-4">
                                <x-heroicon-s-cog-6-tooth class="w-4 h-4" />
                                Konfigurasi Upload
                            </h3>
                            
                            <div class="space-y-4">
                                <div class="relative group">
                                    <select wire:model="upload_package_id" class="w-full pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all appearance-none cursor-pointer">
                                        <option value="">📂 Folder Umum (General)</option>
                                        @foreach($this->packages as $pkg)
                                            <option value="{{ $pkg->id }}">📦 {{ \Illuminate\Support\Str::limit($pkg->name, 30) }}</option>
                                        @endforeach
                                    </select>
                                    <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>

                                <div class="relative group">
                                    <input wire:model="upload_tags_input" type="text" placeholder="Tags: promo, manasik..." 
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-400">
                                    <x-heroicon-s-tag class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-2 bg-slate-50 dark:bg-zinc-900/50 p-1 rounded-2xl border-2 border-dashed border-slate-300 dark:border-white/10 hover:border-indigo-500 dark:hover:border-indigo-500/50 transition-colors relative group cursor-pointer h-full min-h-[180px]">
                            <input type="file" wire:model="photos" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                            
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none z-10 p-6 text-center">
                                
                                <div wire:loading.remove wire:target="photos">
                                    <div class="w-16 h-16 bg-white dark:bg-zinc-800 shadow-lg rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                                        <x-heroicon-s-cloud-arrow-up class="w-8 h-8 text-indigo-500" />
                                    </div>
                                    <h3 class="font-black text-slate-700 dark:text-white text-lg">Tap untuk Upload Aset</h3>
                                    <p class="text-xs font-medium text-slate-400 dark:text-zinc-500 mt-1 max-w-xs mx-auto">
                                        Mendukung Gambar (JPG, PNG) & Video (MP4). <br>
                                        Max size: 10MB per file.
                                    </p>
                                </div>

                                <div wire:loading wire:target="photos" class="flex flex-col items-center">
                                    <x-heroicon-o-arrow-path class="w-12 h-12 text-indigo-500 animate-spin mb-3" />
                                    <p class="text-indigo-600 dark:text-indigo-400 font-black text-sm uppercase tracking-widest animate-pulse">Sedang Mengupload...</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        
                        <div class="flex flex-col sm:flex-row justify-between items-end sm:items-center gap-4 border-b border-slate-200 dark:border-white/5 pb-4">
                            <h3 class="text-base font-black text-slate-800 dark:text-white flex items-center gap-2">
                                <span class="p-1.5 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-lg">
                                    <x-heroicon-s-photo class="w-4 h-4" />
                                </span>
                                Galeri Aset
                            </h3>

                            <div class="flex items-center gap-2">
                                <div class="relative">
                                    <select wire:model.live="filter_asset_type" class="pl-3 pr-8 py-2 bg-white dark:bg-zinc-800 border border-slate-200 dark:border-white/10 rounded-lg text-xs font-bold text-slate-600 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500/20 outline-none appearance-none cursor-pointer">
                                        <option value="all">Semua Tipe</option>
                                        <option value="image">📷 Hanya Foto</option>
                                        <option value="video">🎥 Hanya Video</option>
                                    </select>
                                    <x-heroicon-m-chevron-down class="w-3 h-3 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>

                                <div class="relative">
                                    <select wire:model.live="filter_asset_package" class="pl-3 pr-8 py-2 bg-white dark:bg-zinc-800 border border-slate-200 dark:border-white/10 rounded-lg text-xs font-bold text-slate-600 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500/20 outline-none appearance-none cursor-pointer max-w-[180px]">
                                        <option value="all">Semua Sumber</option>
                                        <option value="general">📂 Folder Umum</option>
                                        @foreach($this->packages as $pkg)
                                            <option value="{{ $pkg->id }}">📦 {{ \Illuminate\Support\Str::limit($pkg->name, 15) }}</option>
                                        @endforeach
                                    </select>
                                    <x-heroicon-m-chevron-down class="w-3 h-3 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                            @forelse($this->assets as $asset)
                            <div class="group relative aspect-square bg-white dark:bg-zinc-900 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:shadow-indigo-500/20 border border-slate-200 dark:border-white/5 transition-all duration-300">
                                
                                @if($asset->file_type == 'image')
                                    <img src="{{ asset('storage/'.$asset->file_path) }}" class="w-full h-full object-cover transition duration-700 group-hover:scale-110" loading="lazy">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-slate-100 dark:bg-zinc-800 text-slate-400 group-hover:bg-slate-200 dark:group-hover:bg-zinc-700 transition">
                                        <x-heroicon-s-video-camera class="w-10 h-10 mb-2" />
                                        <span class="text-[10px] font-black uppercase tracking-widest">Video</span>
                                    </div>
                                @endif
                                
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1 max-w-[85%] z-10 pointer-events-none">
                                    @if($asset->umrah_package_id)
                                        <span class="bg-indigo-600/90 backdrop-blur-md text-white text-[8px] font-bold px-2 py-1 rounded-md shadow-sm border border-white/10">
                                            Grup
                                        </span>
                                    @endif
                                    @if($asset->tags)
                                        @foreach(array_slice($asset->tags, 0, 1) as $tag) 
                                            <span class="bg-black/60 backdrop-blur-md text-white text-[8px] font-bold px-2 py-1 rounded-md shadow-sm border border-white/10">#{{ $tag }}</span>
                                        @endforeach
                                    @endif
                                </div>

                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col justify-end p-4">
                                    <p class="text-white text-xs font-bold truncate mb-3" title="{{ $asset->title }}">{{ $asset->title }}</p>
                                    
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ asset('storage/'.$asset->file_path) }}" download class="bg-white/90 hover:bg-white text-slate-900 text-[10px] py-2 rounded-lg font-black text-center shadow-sm flex items-center justify-center gap-1 transition">
                                            <x-heroicon-s-arrow-down-tray class="w-3 h-3" />
                                            Unduh
                                        </a>

                                        @if($asset->uploaded_by == auth()->id())
                                        <button wire:click="deleteAsset({{ $asset->id }})" wire:confirm="Hapus file ini?" class="bg-red-500/90 hover:bg-red-600 text-white py-2 rounded-lg shadow-sm flex items-center justify-center transition">
                                            <x-heroicon-s-trash class="w-3 h-3" />
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-span-full flex flex-col items-center justify-center py-16 border-2 border-dashed border-slate-200 dark:border-white/5 rounded-3xl">
                                <div class="p-4 bg-slate-50 dark:bg-zinc-800 rounded-full mb-3">
                                    <x-heroicon-o-photo class="w-10 h-10 text-slate-300 dark:text-zinc-600" />
                                </div>
                                <p class="text-sm font-bold text-slate-400 dark:text-zinc-500">Belum ada aset media sesuai filter ini.</p>
                            </div>
                            @endforelse
                        </div>

                        <div class="py-4">
                            {{ $this->assets->links() }}
                        </div>

                    </div>
                </div>
                @endif

                @if($activeTab === 'requests')
                <div class="space-y-8 animate-fade-in">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        
                        <div class="lg:col-span-4 bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-lg border border-slate-100 dark:border-white/5 relative overflow-hidden flex flex-col md:flex-row justify-between items-center">
                            <div class="relative z-10 flex items-center gap-4">
                                <div class="p-3 bg-rose-50 dark:bg-rose-500/10 rounded-2xl text-rose-600 dark:text-rose-400">
                                    <x-heroicon-s-clipboard-document-list class="w-8 h-8" />
                                </div>
                                <div>
                                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                        Design Requests
                                    </h2>
                                    <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-1 uppercase tracking-widest">
                                        Antrian & Status Pengerjaan
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-4 mt-4 md:mt-0 relative z-10">
                                <div class="text-center px-4 py-2 bg-slate-50 dark:bg-zinc-800 rounded-xl border border-slate-100 dark:border-white/5">
                                    <span class="block text-2xl font-black text-rose-500">{{ $this->requests->where('status', 'pending')->count() }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Pending</span>
                                </div>
                                <div class="text-center px-4 py-2 bg-slate-50 dark:bg-zinc-800 rounded-xl border border-slate-100 dark:border-white/5">
                                    <span class="block text-2xl font-black text-blue-500">{{ $this->requests->whereIn('status', ['in_progress', 'review'])->count() }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Proses</span>
                                </div>
                                <div class="text-center px-4 py-2 bg-slate-50 dark:bg-zinc-800 rounded-xl border border-slate-100 dark:border-white/5">
                                    <span class="block text-2xl font-black text-emerald-500">{{ $this->requests->where('status', 'done')->count() }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Selesai</span>
                                </div>
                            </div>

                            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-rose-500/5 to-pink-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($this->requests as $req)
                        <div wire:click="openRequestModal({{ $req->id }})" 
                            class="group bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-200 dark:border-white/5 hover:border-rose-300 dark:hover:border-rose-700 hover:shadow-lg transition-all duration-300 relative overflow-hidden cursor-pointer flex flex-col h-full">
                            
                            <div class="flex justify-between items-start mb-4">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border
                                    {{ match(strtolower($req->priority)) {
                                        'high', 'urgent' => 'bg-red-50 text-red-600 border-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
                                        'medium' => 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800',
                                        default => 'bg-slate-50 text-slate-600 border-slate-100 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700'
                                    } }}">
                                    {{ $req->priority }} Priority
                                </span>
                                <span class="text-[10px] font-bold text-slate-400">{{ $req->created_at->format('d M') }}</span>
                            </div>

                            <div class="flex-1">
                                <h4 class="font-black text-slate-900 dark:text-white text-lg mb-2 group-hover:text-rose-600 transition leading-tight">
                                    {{ $req->title }}
                                </h4>
                                <p class="text-sm text-slate-500 dark:text-zinc-400 line-clamp-3 leading-relaxed mb-4">
                                    {{ $req->description }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-slate-50 dark:border-white/5 mt-auto">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 dark:from-zinc-700 dark:to-zinc-800 flex items-center justify-center text-xs font-black text-slate-600 dark:text-zinc-300 shadow-inner">
                                        {{ substr($req->requester->name, 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700 dark:text-zinc-200">{{ \Illuminate\Support\Str::limit($req->requester->name, 15) }}</span>
                                        <span class="text-[9px] text-slate-400">Requester</span>
                                    </div>
                                </div>

                                <span class="text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5
                                    {{ match($req->status) {
                                        'done' => 'text-emerald-600 dark:text-emerald-400',
                                        'in_progress' => 'text-blue-600 dark:text-blue-400',
                                        'review' => 'text-purple-600 dark:text-purple-400',
                                        default => 'text-slate-500 dark:text-zinc-500'
                                    } }}">
                                    @if($req->status == 'done') <x-heroicon-s-check-circle class="w-4 h-4" />
                                    @elseif($req->status == 'in_progress') <x-heroicon-s-arrow-path class="w-4 h-4 animate-spin" />
                                    @else 
                                    <div class="w-2 h-2 rounded-full bg-current"></div>
                                    @endif
                                    {{ str_replace('_', ' ', $req->status) }}
                                </span>
                            </div>

                            <div class="absolute bottom-0 right-0 w-24 h-24 bg-gradient-to-tl from-rose-500/10 to-transparent rounded-tl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                        </div>
                        @endforeach
                    </div>

                    @if($this->requests->isEmpty())
                    <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-200 dark:border-white/5 rounded-[3rem]">
                        <div class="p-6 bg-slate-50 dark:bg-zinc-800 rounded-full mb-4">
                            <x-heroicon-o-clipboard-document class="w-12 h-12 text-slate-300 dark:text-zinc-600" />
                        </div>
                        <h3 class="text-lg font-black text-slate-700 dark:text-white uppercase tracking-tight mb-1">Belum Ada Request</h3>
                        <p class="text-sm text-slate-400 dark:text-zinc-500">Saat ini belum ada antrian desain yang masuk.</p>
                    </div>
                    @endif

                </div>
                @endif
            </div>

            <nav class="md:hidden fixed bottom-6 left-4 right-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-lg border border-slate-200 dark:border-white/5 flex justify-around items-center py-3 z-40 rounded-3xl shadow-[0_10px_30px_-10px_rgba(0,0,0,0.2)]"
                style="
                    background-image: url('/images/ornaments/arabesque.png');
                    background-repeat: repeat;
                    background-size: 150px 150px;
                ">

                <button wire:click="setTab('calendar')" 
                    class="relative flex flex-col items-center gap-1 w-20 transition-all duration-300 {{ $activeTab === 'calendar' ? 'text-cyan-600 dark:text-cyan-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                    @if($activeTab === 'calendar')
                        <div class="absolute -top-3 w-1 h-1 bg-cyan-600 rounded-full shadow-[0_0_8px_#06b6d4]"></div>
                    @endif
                    <x-heroicon-s-calendar-days class="w-6 h-6" />
                    <span class="text-[10px] font-black uppercase tracking-tighter">Calendar</span>
                </button>

                <button wire:click="setTab('assets')" 
                    class="relative flex flex-col items-center gap-1 w-20 transition-all duration-300 {{ $activeTab === 'assets' ? 'text-indigo-600 dark:text-indigo-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                    @if($activeTab === 'assets')
                        <div class="absolute -top-3 w-1 h-1 bg-indigo-600 rounded-full shadow-[0_0_8px_#4f46e5]"></div>
                    @endif
                    <x-heroicon-s-photo class="w-6 h-6" />
                    <span class="text-[10px] font-black uppercase tracking-tighter">Assets</span>
                </button>

                <button wire:click="setTab('requests')" 
                    class="relative flex flex-col items-center gap-1 w-20 transition-all duration-300 {{ $activeTab === 'requests' ? 'text-rose-600 dark:text-rose-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                    @if($activeTab === 'requests')
                        <div class="absolute -top-3 w-1 h-1 bg-rose-600 rounded-full shadow-[0_0_8px_#e11d48]"></div>
                    @endif
                    <div class="relative">
                        <x-heroicon-s-clipboard-document-list class="w-6 h-6" />
                        @if($this->requests && $this->requests->where('status', 'pending')->count() > 0)
                            <span class="absolute -top-1 -right-1 block h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-zinc-900 animate-pulse"></span>
                        @endif
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-tighter">Request</span>
                </button>

            </nav>

            <div x-show="showScheduleModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
    
                <div @click="showScheduleModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

                <div class="relative bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh] border border-white/10 overflow-hidden" x-transition.scale>
                    
                    <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                {{ $sched_id ? 'Edit Jadwal' : 'Jadwal Baru' }}
                            </h3>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-widest mt-1">
                                Content Planner
                            </p>
                        </div>
                        <button @click="showScheduleModal = false" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                            <x-heroicon-s-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-6 space-y-5 overflow-y-auto custom-scrollbar flex-1">
                        
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Judul Konten</label>
                            <div class="relative">
                                <input wire:model="sched_title" type="text" placeholder="Contoh: Promo Umrah Ramadhan..." 
                                    class="w-full pl-4 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-cyan-500/50 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all placeholder:text-slate-400">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Tanggal Tayang</label>
                                <div class="relative">
                                    <input wire:model="sched_date" type="date" 
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-cyan-500/50 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all cursor-pointer">
                                    <x-heroicon-s-calendar class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Status</label>
                                <div class="relative">
                                    <select wire:model="sched_status"
                                        class="w-full pl-10 pr-8 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-cyan-500/50 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all appearance-none cursor-pointer">
                                        <option value="idea">💡 Idea</option>
                                        <option value="drafting">📝 Drafting</option>
                                        <option value="ready">✅ Ready</option>
                                        <option value="published">🚀 Published</option>
                                    </select>
                                    
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                        @if($sched_status === 'idea') <x-heroicon-s-light-bulb class="w-5 h-5 text-yellow-500" />
                                        @elseif($sched_status === 'drafting') <x-heroicon-s-pencil-square class="w-5 h-5 text-blue-500" />
                                        @elseif($sched_status === 'ready') <x-heroicon-s-check-circle class="w-5 h-5 text-emerald-500" />
                                        @else <x-heroicon-s-rocket-launch class="w-5 h-5 text-purple-500" />
                                        @endif
                                    </div>
                                    <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block ml-1">Platform Target</label>
                            <div class="flex flex-wrap gap-3">
                                <label class="cursor-pointer group">
                                    <input type="checkbox" wire:model="sched_platforms" value="instagram" class="peer sr-only">
                                    <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-slate-100 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-500 peer-checked:border-pink-500 peer-checked:bg-pink-50 peer-checked:text-pink-600 transition-all font-bold text-xs uppercase tracking-wide group-hover:border-pink-200">
                                        <span class="text-lg">📸</span> Instagram
                                    </div>
                                </label>
                                <label class="cursor-pointer group">
                                    <input type="checkbox" wire:model="sched_platforms" value="tiktok" class="peer sr-only">
                                    <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-slate-100 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-500 peer-checked:border-black peer-checked:bg-slate-900 peer-checked:text-white transition-all font-bold text-xs uppercase tracking-wide group-hover:border-slate-300">
                                        <span class="text-lg">🎵</span> TikTok
                                    </div>
                                </label>
                                <label class="cursor-pointer group">
                                    <input type="checkbox" wire:model="sched_platforms" value="facebook" class="peer sr-only">
                                    <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-slate-100 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-600 transition-all font-bold text-xs uppercase tracking-wide group-hover:border-blue-200">
                                        <span class="text-lg">📘</span> Facebook
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Draft Caption / Notes</label>
                            <div class="relative group">
                                <textarea wire:model="sched_caption" rows="4" placeholder="Tulis ide caption di sini..."
                                    class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-medium text-slate-700 dark:text-zinc-200 focus:border-cyan-500/50 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all resize-none"></textarea>
                            </div>
                        </div>

                        <div x-show="$wire.sched_status === 'published'" x-transition 
                            class="bg-emerald-50/50 dark:bg-emerald-900/10 p-5 rounded-2xl border border-emerald-100 dark:border-emerald-800/30 space-y-4">
                            
                            <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase flex items-center gap-2 tracking-widest">
                                <x-heroicon-s-link class="w-4 h-4" /> Link Postingan (Wajib)
                            </p>

                            @if(empty($sched_platforms))
                                <p class="text-xs text-slate-400 italic font-medium ml-1">Pilih platform dulu di atas.</p>
                            @else
                                @foreach($sched_platforms as $plat)
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 dark:text-zinc-400 uppercase mb-1 block ml-1">
                                        Link {{ ucfirst($plat) }}
                                    </label>
                                    <div class="relative">
                                        <input wire:model="sched_links.{{ $plat }}" type="url" placeholder="https://..." 
                                            class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-zinc-900 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs font-bold text-slate-700 dark:text-zinc-200 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all">
                                        <x-heroicon-m-link class="w-4 h-4 text-emerald-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                    </div>
                                    @error("sched_links.$plat") <span class="text-[10px] text-red-500 font-bold ml-1 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                @endforeach
                            @endif
                        </div>

                    </div>

                    <div class="p-5 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-zinc-900/80 flex justify-end gap-3 shrink-0">
                        <button @click="showScheduleModal = false" class="px-6 py-2.5 bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-300 transition">
                            Batal
                        </button>
                        <button wire:click="saveSchedule" class="px-6 py-2.5 bg-cyan-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-cyan-700 shadow-lg shadow-cyan-500/30 transition transform active:scale-95">
                            Simpan Jadwal
                        </button>
                    </div>

                </div>
            </div>

            <div x-show="showRequestModal" class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-6" style="display: none;" x-transition.opacity>
                
                <div @click="showRequestModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

                <div class="relative bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh] border border-white/10 overflow-hidden" x-transition.scale>
                    
                    <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Proses Request</h3>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-widest mt-1">Update Status & Upload Hasil</p>
                        </div>
                        <button @click="showRequestModal = false" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                            <x-heroicon-s-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                        
                        <div class="bg-indigo-50 dark:bg-indigo-900/10 p-5 rounded-2xl border border-indigo-100 dark:border-indigo-800/30 relative overflow-hidden">
                            <div class="relative z-10">
                                <h4 class="font-black text-indigo-900 dark:text-white text-base mb-1">{{ $editReqTitle }}</h4>
                                <p class="text-sm text-indigo-700/80 dark:text-indigo-300/80 mb-3 leading-relaxed">{{ $editReqDesc }}</p>
                                <div class="inline-flex items-center gap-1.5 bg-white dark:bg-zinc-800 px-3 py-1.5 rounded-lg border border-indigo-100 dark:border-white/5 shadow-sm">
                                    <x-heroicon-s-clock class="w-3.5 h-3.5 text-indigo-500" />
                                    <span class="text-[10px] font-bold text-slate-600 dark:text-zinc-300 uppercase tracking-wide">
                                        Deadline: {{ $this->requests->find($editReqId)?->deadline?->format('d M Y') ?? '-' }}
                                    </span>
                                </div>
                            </div>
                            <x-heroicon-o-clipboard-document-list class="absolute -right-4 -bottom-4 w-24 h-24 text-indigo-500/10 rotate-12" />
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Update Status</label>
                            <div class="relative">
                                <select wire:model="editReqStatus" class="w-full pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="pending">🕒 Pending (Menunggu)</option>
                                    <option value="in_progress">🔨 In Progress (Sedang Dikerjakan)</option>
                                    <option value="review">👀 In Review (Revisi/Cek)</option>
                                    <option value="done">✅ Done (Selesai)</option>
                                </select>
                                <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Upload Hasil (Gambar/Zip/Video)</label>
                            
                            <div class="border-2 border-dashed border-slate-300 dark:border-zinc-700 hover:border-indigo-500 dark:hover:border-indigo-500 bg-slate-50/50 dark:bg-zinc-800/30 rounded-2xl p-6 text-center transition-all relative group cursor-pointer">
                                <input type="file" wire:model="editReqResultFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                
                                <div class="space-y-2 pointer-events-none">
                                    <div wire:loading wire:target="editReqResultFile">
                                        <x-heroicon-o-arrow-path class="w-10 h-10 mx-auto text-indigo-500 animate-spin" />
                                        <p class="text-xs text-indigo-600 font-bold uppercase tracking-widest mt-2">Uploading...</p>
                                    </div>
                                    <div wire:loading.remove wire:target="editReqResultFile">
                                        <div class="w-12 h-12 bg-white dark:bg-zinc-700 shadow-sm rounded-full flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition text-indigo-500">
                                            <x-heroicon-s-paper-clip class="w-6 h-6" />
                                        </div>
                                        <p class="text-xs font-bold text-slate-600 dark:text-zinc-300 group-hover:text-indigo-600 transition">Klik untuk Pilih File</p>
                                        @if($editReqResultFile)
                                            <p class="text-[10px] text-emerald-500 font-bold mt-1">File Terpilih: {{ $editReqResultFile->getClientOriginalName() }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @error('editReqResultFile') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror

                            @if($editReqExistingResult)
                                <a href="{{ asset('storage/'.$editReqExistingResult) }}" target="_blank" 
                                class="mt-3 flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/10 p-3 rounded-xl border border-emerald-100 dark:border-emerald-800/30 group hover:border-emerald-300 transition">
                                    <div class="p-2 bg-white dark:bg-zinc-800 rounded-lg text-emerald-500 shadow-sm">
                                        <x-heroicon-s-document-check class="w-5 h-5" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 truncate">Lihat File Hasil Sebelumnya</p>
                                        <p class="text-[10px] text-emerald-500/70">Klik untuk membuka</p>
                                    </div>
                                    <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 text-emerald-400" />
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-zinc-900/80 flex justify-end gap-3 shrink-0">
                        <button @click="showRequestModal = false" class="px-6 py-2.5 bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-300 transition">
                            Batal
                        </button>
                        <button wire:click="updateRequest" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition transform active:scale-95">
                            Simpan Perubahan
                        </button>
                    </div>

                </div>
            </div>

        </main>

    </div>

</div>
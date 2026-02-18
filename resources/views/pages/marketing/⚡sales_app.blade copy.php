<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Lead;
use App\Models\Task;
use App\Models\SalesTarget; 
use App\Models\Booking;
use App\Models\CorporateLead;
use App\Models\MarketingReport;
use App\Models\Agent;
use App\Models\Employee;
use App\Models\UmrahPackage;
use App\Models\Jamaah;
use App\Models\User;
use App\Models\Payment;
use App\Models\OfficeWallet;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\WidgetsWorkspace\PersonalPerformanceStats;
use Filament\Notifications\Notification;
use App\Models\MediaAsset;
use App\Models\ContentRequest;

new #[Layout('layouts::marketing')] class extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $activeTab = 'home';

    public $searchLead = '';
    public $filterStatus = 'cold';
    
    public $selectedMonth;

    public $leadType = 'personal';

    public $name, $phone, $city, $source, $notes;

    public $company_name, $pic_name, $pic_phone, $address, $potential_pax;

    public $report_desc, $report_loc, $report_qty, $report_type = 'canvasing';
    public $agent_id;
    public $sales_id; 
    public $potential_package;
    public $status = 'cold'; 
    public $packages = [];

    public $agents = [];
    public $employees = [];

    public $convertLeadId;
    public $convertMode = 'new';
    
    public $convertStep = 1;

    public $booking_package_id;
    public $booking_price = 0;
    public $booking_notes;
    public $dp_amount = 10000000;
    public $dp_method = 'transfer';
    public $dp_proof;
    public $wallets = [];
    public $target_wallet_id;

    public $packagesList = [];
    
    public $new_name, $new_email, $new_password, $new_nik, $new_gender, $new_phone, $new_address;
    public $new_passport_number, $new_passport_expiry, $new_shirt_size;

    public $existing_jamaah_id;
    public $jamaahSearchResults = [];

    public $editLeadId;
    public $isEditing = false;

    public $showMediaModal = false;
    public $mediaTab = 'upload';
    public $mediaPhotos = [];
    public $mediaTags;
    public $selectedPackageId;
    
    // State Request
    public $reqTitle, $reqDesc, $reqDeadline, $reqPriority = 'medium';

    public function mount()
    {
        $this->packages = UmrahPackage::where('departure_date', '>', now())
            ->orderBy('departure_date', 'asc')
            ->get()
            ->mapWithKeys(function ($item) {
                $label = $item->name . ' (' . Carbon::parse($item->departure_date)->format('d M') . ')';
                return [$item->name => $label]; 
            });

        $this->agents = Agent::orderBy('name')->pluck('name', 'id');
        
        $this->sales_id = Auth::user()->employee?->id;

        $this->packagesList = UmrahPackage::where('departure_date', '>', now())
            ->orderBy('departure_date', 'asc')
            ->get();
        
        $this->wallets = OfficeWallet::where('is_active', true)->get();

        $this->selectedMonth = now()->format('Y-m');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage(); 
    }

    public function getStatsProperty()
    {
        return (new PersonalPerformanceStats)->getData(); 
    }

    public function getLeadStatsProperty()
    {
        $id = Auth::user()->employee?->id;
        $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $this->selectedMonth)->endOfMonth();

        $leads = Lead::where('sales_id', $id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $total = $leads->count();
        $converted = $leads->where('status', 'converted')->count();
        
        $ratio = $total > 0 ? ($converted / $total) * 100 : 0;

        $breakdown = [
            'cold' => $leads->where('status', 'cold')->count(),
            'warm' => $leads->where('status', 'warm')->count(),
            'hot'  => $leads->where('status', 'hot')->count(),
        ];

        return [
            'total' => $total,
            'converted' => $converted,
            'ratio' => round($ratio, 1),
            'breakdown' => $breakdown
        ];
    }

    public function getLeadsProperty()
    {
        $id = Auth::user()->employee?->id;
        $query = null;

        if ($this->leadType === 'converted') {
            $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);
            
            return Lead::query()
                ->where('sales_id', $id)
                ->where('status', 'converted')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->latest()
                ->paginate(10);
        }
        elseif ($this->leadType === 'corporate') {
            return CorporateLead::query()
                ->where('sales_id', $id)
                ->when($this->searchLead, fn($q) => $q->where('company_name', 'like', "%{$this->searchLead}%"))
                ->latest()
                ->paginate(10);
        }
        else {
            return Lead::query()
                ->where('sales_id', $id)
                ->when($this->searchLead, fn($q) => $q->where('name', 'like', "%{$this->searchLead}%"))
                ->when($this->filterStatus, function($q) {
                    $q->where('status', $this->filterStatus);
                }, function($q) {
                    $q->where('status', '!=', 'converted');
                })
                ->latest()
                ->paginate(10);
        }
    }

    public function getPerformanceProperty()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return [
                'target' => 0,
                'realisasi' => 0,
                'persen' => 0,
                'color' => 'gray',
                'label' => 'Belum ada data Employee'
            ];
        }

        $targetObj = SalesTarget::where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', now()) 
            ->whereDate('end_date', '>=', now())   
            ->latest() 
            ->first();

        $targetAngka = $targetObj ? $targetObj->target_jamaah : 0;

        $queryRealisasi = Booking::where('sales_id', $employee->id)
            ->where('status', '!=', 'cancelled');

        if ($targetObj) {
            $queryRealisasi->whereBetween('created_at', [
                $targetObj->start_date, 
                $targetObj->end_date . ' 23:59:59'
            ]);
        } else {
            $queryRealisasi->whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year);
        }

        $realisasi = $queryRealisasi->count();

        $persen = $targetAngka > 0 ? ($realisasi / $targetAngka) * 100 : 0;
        
        $color = 'text-red-500 bg-red-500'; 
        if ($persen >= 100) $color = 'text-green-500 bg-green-500';
        elseif ($persen >= 50) $color = 'text-yellow-500 bg-yellow-500';

        return [
            'target' => $targetAngka,
            'realisasi' => $realisasi,
            'persen' => round($persen),
            'color' => $color,
            'period' => $targetObj ? Carbon::parse($targetObj->start_date)->format('M Y') : now()->format('M Y')
        ];
    }

    public function getMyTasksProperty()
    {
        return Task::query()
            ->with('template')
            ->where('employee_id', Auth::user()->employee?->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function executeTask($taskId)
    {
        $task = \App\Models\Task::with('template')->find($taskId);
        if (!$task) return;

        $url = $task->action_url ?? $task->template?->action_url;

        if (empty($url)) {
            Notification::make()->title('Tidak ada link pengerjaan')->warning()->send();
            return;
        }

        if (str_contains($url, 'wa.me') || str_contains($url, 'whatsapp.com')) {
            $this->js("window.open('$url', '_blank')");
            return;
        }

        if (str_contains($url, '/leads') || str_contains($url, 'corporate-leads')) {
            $this->setTab('leads'); // Pindah Tab aja, jangan reload page
            return;
        }

        if (str_contains($url, '/marketing-reports')) {
            $this->report_desc = "Pengerjaan Tugas: " . $task->title; 
            $this->showReportModal = true; 
            return;
        }

        if (str_contains($url, '/bookings/create')) {
            $this->convertMode = 'existing'; // Asumsi cari jamaah existing
            $this->showConvertModal = true;
            $this->convertStep = 1;
            return;
        }

        $this->js("window.open('$url', '_self')");
    }

    public function saveLead()
    {
        if ($this->leadType === 'personal') {
            $this->validate([
                'name' => 'required',
                'phone' => 'required|numeric|digits_between:10,15|unique:leads,phone',
                'source' => 'required',
                'sales_id' => 'required',
                'agent_id' => $this->source === 'Agent' ? 'required' : 'nullable',
            ],[
                'phone.unique' => 'Nomor telepon sudah terdaftar di data lead.'
            ]);
        } else {
            $this->validate([
                'company_name' => 'required',
                'pic_name' => 'required',
                'pic_phone' => 'required|numeric|digits_between:10,15|unique:leads,phone',
                'sales_id' => 'required',
            ], [
                'pic_phone.unique' => 'Nomor telepon sudah terdaftar di data lead.'
            ]);
        }

        if ($this->isEditing) {
            if ($this->leadType === 'personal') {
                $lead = Lead::find($this->editLeadId);
                if ($lead) {
                    $lead->update([
                        'sales_id' => $this->sales_id,
                        'agent_id' => $this->source === 'Agent' ? $this->agent_id : null,
                        'name' => $this->name,
                        'phone' => $this->phone,
                        'city' => $this->city,
                        'source' => $this->source,
                        'potential_package' => $this->potential_package,
                        'status' => $this->status,
                        'notes' => $this->notes,
                    ]);
                }
            } else {
                $lead = CorporateLead::find($this->editLeadId);
                if ($lead) {
                    $lead->update([
                        'sales_id' => $this->sales_id,
                        'company_name' => $this->company_name,
                        'pic_name' => $this->pic_name,
                        'pic_phone' => $this->pic_phone,
                        'address' => $this->address,
                        'potential_pax' => $this->potential_pax ?? 0,
                        'status' => $this->status,
                        'notes' => $this->notes
                    ]);
                }
            }
            Notification::make()->title('Data Berhasil Diupdate')->success()->send();
        } 
        
        else {
            if ($this->leadType === 'personal') {
                Lead::create([
                    'sales_id' => $this->sales_id,
                    'agent_id' => $this->source === 'Agent' ? $this->agent_id : null,
                    'name' => $this->name,
                    'phone' => $this->phone,
                    'city' => $this->city,
                    'source' => $this->source,
                    'potential_package' => $this->potential_package,
                    'status' => 'new',
                    'notes' => $this->notes,
                ]);
            } else {
                CorporateLead::create([
                    'sales_id' => $this->sales_id,
                    'company_name' => $this->company_name,
                    'pic_name' => $this->pic_name,
                    'pic_phone' => $this->pic_phone,
                    'address' => $this->address,
                    'potential_pax' => $this->potential_pax ?? 0,
                    'budget_estimation' => 0,
                    'status' => 'prospecting',
                    'notes' => $this->notes
                ]);
            }
            Notification::make()->title('Lead Berhasil Ditambahkan')->success()->send();
        }

        $this->resetForm();
        $this->dispatch('close-modal'); 
    }

    public function createLead() {
        if ($this->leadType === 'personal') {
             Lead::create([
                'sales_id' => $this->sales_id, 
                'agent_id' => $this->source === 'Agent' ? $this->agent_id : null,
                'name' => $this->name,
                'phone' => $this->phone,
                'city' => $this->city,
                'source' => $this->source,
                'potential_package' => $this->potential_package,
                'status' => 'new',
                'notes' => $this->notes,
            ]);
        } else {
             CorporateLead::create([
                'sales_id' => $this->sales_id,
                'company_name' => $this->company_name,
                'pic_name' => $this->pic_name,
                'pic_phone' => $this->pic_phone,
                'address' => $this->address,
                'potential_pax' => $this->potential_pax ?? 0,
                'status' => 'prospecting',
                'notes' => $this->notes
            ]);
        }
        Notification::make()->title('Lead Berhasil Ditambahkan')->success()->send();
    }

    public function resetForm()
    {
        $this->reset([
            'name', 'phone', 'city', 'source', 'notes', 'agent_id', 'potential_package', 'status',
            'company_name', 'pic_name', 'pic_phone', 'address', 'potential_pax',
            'isEditing', 'editLeadId'
        ]);
        
        $this->sales_id = Auth::user()->employee?->id;
        
        $this->status = 'cold';
    }

    public function openEditLead($leadId)
    {
        $this->isEditing = true;
        
        if ($this->leadType === 'personal') {
            $lead = Lead::find($leadId);
            $this->editLeadId = $lead->id;
            $this->name = $lead->name;
            $this->phone = $lead->phone;
            $this->city = $lead->city;
            $this->source = $lead->source;
            $this->potential_package = $lead->potential_package;
            $this->status = $lead->status;
            $this->notes = $lead->notes;
            $this->agent_id = $lead->agent_id;
            $this->sales_id = $lead->sales_id;
        } else {
            $lead = CorporateLead::find($leadId);
            $this->editLeadId = $lead->id;
            $this->company_name = $lead->company_name;
            $this->pic_name = $lead->pic_name;
            $this->pic_phone = $lead->pic_phone;
            $this->address = $lead->address;
            $this->potential_pax = $lead->potential_pax;
            $this->status = $lead->status;
            $this->notes = $lead->notes;
            $this->sales_id = $lead->sales_id;
        }

        $this->dispatch('open-add-lead-modal');
    }

    // --- ACTION: SIMPAN LAPORAN KEGIATAN (Manual) ---
    public function saveActivityReport()
    {
        $this->validate([
            'report_desc' => 'required',
            'report_type' => 'required',
        ]);

        MarketingReport::create([
            'employee_id' => Auth::user()->employee?->id,
            'date' => now(),
            'activity_type' => $this->report_type,
            'description' => $this->report_desc,
            'location_name' => $this->report_loc,
            'prospect_qty' => $this->report_qty ?? 0,
        ]);

        $this->reset(['report_desc', 'report_loc', 'report_qty', 'report_type']);
        $this->dispatch('close-modal');
        Notification::make()->title('Laporan Kegiatan Terkirim')->success()->send();
    }

    public function openConvertModal($leadId)
    {
        $lead = Lead::find($leadId);
        if (!$lead) return;

        $this->convertLeadId = $leadId;
        $this->convertMode = 'new';

        $this->new_name = $lead->name;
        $this->new_phone = $lead->phone;
        $this->new_address = $lead->city;
        $this->new_email = ''; 
        
        $this->dispatch('open-convert-modal');
    }

    public function updatedExistingJamaahId($val)
    {
        // Fitur ini agak tricky di mobile kalau pakai select biasa.
        // Nanti kita pakai Datalist atau Select simple saja.
    }

    public function goToStep2()
    {
        if ($this->convertMode === 'existing' && !$this->existing_jamaah_id) {
            $this->addError('existing_jamaah_id', 'Pilih jamaah dulu bro!');
            return;
        }
        $this->convertStep = 2;
    }

    public function goToStep3()
    {
        if ($this->convertMode === 'new') {
            $this->validate([
                'new_name' => 'required',
                'new_nik' => 'required|numeric|digits:16|unique:jamaahs,nik',
                'new_phone' => 'required',
                'new_email' => 'required|email|unique:users,email',
            ]);
        }
        
        $lead = Lead::find($this->convertLeadId);
        if ($lead && $lead->potential_package) {
            $matchedPackage = $this->packagesList->firstWhere('name', $lead->potential_package);
            if ($matchedPackage) {
                $this->booking_package_id = $matchedPackage->id;
                $this->updatedBookingPackageId($matchedPackage->id);
            }
        }

        $this->convertStep = 3;
    }

    public function updatedBookingPackageId($val)
    {
        $paket = $this->packagesList->firstWhere('id', $val);
        $this->booking_price = $paket ? $paket->price : 0;
    }

    public function processFinalConversion()
    {
        $this->validate([
            'booking_package_id' => 'required',
            'dp_amount' => 'required|numeric|min:1000000',
            'dp_method' => 'required',
            'target_wallet_id' => 'required',
            'dp_proof' => 'required|image|max:2048',
        ]);

        DB::transaction(function () {
            $lead = Lead::find($this->convertLeadId);
            
            $jamaahId = null;
            if ($this->convertMode === 'existing') {
                $jamaahId = $this->existing_jamaah_id;
            } else {
                $user = User::create([
                    'name' => $this->new_name,
                    'email' => $this->new_email,
                    'password' => Hash::make($this->new_password ?? Str::random(8)),
                    'is_active' => true,
                ]);
                $user->assignRole('jamaah');

                $jamaah = Jamaah::create([
                    'user_id' => $user->id,
                    'agent_id' => $lead->agent_id,
                    'name' => $this->new_name,
                    'nik' => $this->new_nik,
                    'gender' => $this->new_gender,
                    'phone' => $this->new_phone,
                    'address' => $this->new_address,
                    'passport_number' => $this->new_passport_number,
                    'passport_expiry' => $this->new_passport_expiry,
                    'shirt_size' => $this->new_shirt_size,
                    'status' => 'active'
                ]);
                $jamaahId = $jamaah->id;
            }

            $booking = Booking::create([
                'booking_code' => 'RZ-' . strtoupper(uniqid()),
                'jamaah_id' => $jamaahId,
                'umrah_package_id' => $this->booking_package_id,
                'sales_id' => Auth::user()->employee?->id,
                'agent_id' => $lead->agent_id,
                'total_price' => $this->booking_price,
                'status' => 'booking',
                'notes' => $this->booking_notes,
                'created_at' => now(),
            ]);

            $proofPath = $this->dp_proof->store('payments', 'public');
            
            $wallet = OfficeWallet::find($this->target_wallet_id);
            $note = "DP Awal. Target: " . ($wallet->name ?? '-');

            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $this->dp_amount,
                'type' => 'dp',
                'method' => $this->dp_method,
                'office_wallet_id' => $this->target_wallet_id,
                'proof_file' => $proofPath,
                'notes' => $note,
                'created_at' => now(),
                'verified_by' => null,
                'verified_at' => null,
            ]);

            $lead->update(['status' => 'converted']);
        });

        $this->reset(['convertStep', 'dp_proof', 'new_name', 'convertLeadId', 'target_wallet_id']);

        $this->dispatch('close-modal');
        

        Notification::make()
            ->title('Alhamdulillah! Booking Berhasil 🕋')
            ->body('Data jamaah dan pembayaran telah tersimpan.')
            ->success()
            ->send();
    }

    // --- ACTION: PROSES KONVERSI ---
    public function processConvert()
    {
        $lead = Lead::find($this->convertLeadId);
        if (!$lead) return;

        $targetJamaahId = null;

        if ($this->convertMode === 'existing') {
            $this->validate([
                'existing_jamaah_id' => 'required|exists:jamaahs,id'
            ]);
            $targetJamaahId = $this->existing_jamaah_id;
        } 
        else {
            $this->validate([
                'new_name' => 'required',
                'new_email' => 'required|email|unique:users,email',
                'new_password' => 'required|min:6',
                'new_nik' => 'required|numeric|digits:16|unique:jamaahs,nik',
                'new_phone' => 'required',
                'new_gender' => 'required',
            ]);

            $targetJamaahId = DB::transaction(function () use ($lead) {
                $user = User::create([
                    'name' => $this->new_name,
                    'email' => $this->new_email,
                    'password' => Hash::make($this->new_password),
                    'is_active' => true,
                ]);
                $user->assignRole('jamaah'); 

                // 2. Buat Data Jamaah
                $jamaah = Jamaah::create([
                    'user_id' => $user->id,
                    'agent_id' => $lead->agent_id, 
                    'name' => $this->new_name,
                    'nik' => $this->new_nik,
                    'gender' => $this->new_gender,
                    'phone_number' => $this->new_phone,
                    'address' => $this->new_address,
                    'passport_number' => $this->new_passport_number,
                    'passport_expiry' => $this->new_passport_expiry,
                    'shirt_size' => $this->new_shirt_size,
                    'status' => 'active'
                ]);

                return $jamaah->id;
            });
        }

        $lead->update(['status' => 'converted']);

        $url = BookingResource::getUrl('create', [
            'jamaah_id' => $targetJamaahId,
            'sales_id' => Auth::user()->employee?->id,
            'agent_id' => $lead->agent_id,
        ]);

        return redirect()->to($url);
    }

    public function getMediaPackagesProperty()
    {
        return UmrahPackage::orderBy('departure_date', 'desc')->take(20)->get();
    }

    // ACTION: UPLOAD ASSET
    public function saveMediaAssets()
    {
        $this->validate([
            'mediaPhotos.*' => 'image|max:10240',
        ]);

        foreach ($this->mediaPhotos as $photo) {
            $path = $photo->store('media-assets', 'public');
            
            // Tagging otomatis sesuai divisi
            $divisi = $this instanceof FinanceCenter ? 'finance' : 'sales';
            $tags = [$divisi, 'upload-manual'];
            
            if($this->mediaTags) {
                $tags = array_merge($tags, array_map('trim', explode(',', $this->mediaTags)));
            }

            MediaAsset::create([
                'file_path' => $path,
                'file_type' => 'image',
                'umrah_package_id' => $this->selectedPackageId ?: null,
                'tags' => $tags,
                'uploaded_by' => auth()->id(),
                'title' => $photo->getClientOriginalName(),
            ]);
        }

        $this->reset(['mediaPhotos', 'mediaTags', 'selectedPackageId']);
        Notification::make()->title('File Berhasil Diupload 📂')->success()->send();
        $this->showMediaModal = false;
    }

    // ACTION: KIRIM REQUEST KE MEDIA
    public function saveContentRequest()
    {
        $this->validate([
            'reqTitle' => 'required',
            'reqDesc' => 'required',
            'reqDeadline' => 'nullable|date',
        ]);

        ContentRequest::create([
            'requester_id' => auth()->id(),
            'title' => $this->reqTitle,
            'description' => $this->reqDesc,
            'deadline' => $this->reqDeadline,
            'priority' => $this->reqPriority,
            'status' => 'pending'
        ]);

        $this->reset(['reqTitle', 'reqDesc', 'reqDeadline', 'reqPriority']);
        Notification::make()->title('Request Terkirim ke Tim Media 📝')->success()->send();
        $this->showMediaModal = false;
    }
};
?>

<div x-data="{ 
        darkMode: localStorage.getItem('theme') === 'dark',
        showMediaModal: @entangle('showMediaModal'),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) { document.documentElement.classList.add('dark'); }
            else { document.documentElement.classList.remove('dark'); }
        },
        showLeadModal: false, 
        showReportModal: false
    }" 
    x-on:close-modal.window="showLeadModal = false; showReportModal = false"
    x-on:open-add-lead-modal.window="showLeadModal = true"
    class="flex flex-col h-full w-full relative bg-gray-50 dark:bg-zinc-950 text-gray-900 dark:text-zinc-100">
    <div class="absolute -bottom-24 -right-24 w-128 h-128 opacity-40 dark:opacity-40 pointer-events-none transform">
        <img src="{{ asset('images/icons/kabah1.png') }}" alt="Kabah Decoration" class="w-full h-full object-contain">
    </div>
    <header 
        class="bg-white dark:bg-zinc-900 border-b border-gray-200 dark:border-white/5 px-4 py-3 sticky top-0 z-30 shadow-sm shrink-0"
        style="
            background-image: url('/images/ornaments/arabesque.png');
            background-repeat: repeat;
            background-size: 150px 150px;
        ">
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-bold text-lg text-emerald-600 dark:text-emerald-400">Marketing Command Center</h1>
                <p class="text-[10px] text-gray-500 dark:text-zinc-400">Hi, {{ auth()->user()->name }} 👋</p>
            </div>

            <div class="flex items-center gap-3">
                <button @click="toggleTheme()" class="p-2 rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 dark:text-zinc-500 transition">
                    <x-heroicon-o-moon class="w-5 h-5" x-show="!darkMode" />
                    <x-heroicon-o-sun class="w-5 h-5" x-show="darkMode" style="display: none;" />
                </button>

                <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-bold text-sm border border-emerald-200 dark:border-emerald-800">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full overflow-y-auto p-4 pb-24 custom-scrollbar relative">
        
        @if($activeTab === 'home')
            <div class="space-y-6 animate-fade-in">
                
                <div class="bg-gradient-to-r from-emerald-600 to-green-400 rounded-2xl p-6 text-white shadow-lg">
                    <h2 class="text-2xl font-bold">Semangat Pagi!</h2>
                    <p class="text-emerald-50 text-sm mt-1">Kejar targetmu hari ini.</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-white/5 flex flex-row items-center justify-between gap-4">
        
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <x-heroicon-m-swatch class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-white text-sm">Creative Support</h3>
                            <p class="text-xs text-gray-500 dark:text-zinc-400">Upload bukti / Request konten</p>
                        </div>
                    </div>

                    <button wire:click="$set('showMediaModal', true)" 
                        class="shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold flex items-center gap-2 transition shadow-md shadow-indigo-500/20 active:scale-95">
                        <x-heroicon-m-camera class="w-4 h-4" />
                        <span class="hidden sm:inline">Buka</span> Tools
                    </button>

                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-white/5 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Target • {{ $this->performance['period'] }}</p>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mt-1">Closingan Bulan Ini</h3>
                        </div>
                        <div class="px-3 py-1 rounded-lg bg-gray-100 dark:bg-white/5 font-bold text-sm {{ explode(' ', $this->performance['color'])[0] }}">
                            {{ $this->performance['persen'] }}%
                        </div>
                    </div>

                    <div class="flex items-end gap-2 mb-3">
                        <span class="text-4xl font-black text-gray-900 dark:text-white">{{ $this->performance['realisasi'] }}</span>
                        <span class="text-gray-400 text-sm font-bold mb-1.5">/ {{ $this->performance['target'] }} Jamaah</span>
                    </div>

                    <div class="w-full bg-gray-100 dark:bg-zinc-700 rounded-full h-3">
                        <div class="h-3 rounded-full transition-all duration-1000 ease-out {{ explode(' ', $this->performance['color'])[1] }}" 
                             style="width: {{ min($this->performance['persen'], 100) }}%">
                        </div>
                    </div>

                    <div class="mt-3 text-[10px] text-gray-400 text-right flex items-center justify-end gap-1">
                        @if($this->performance['persen'] >= 100)
                            <x-heroicon-o-sparkles class="w-3 h-3 text-emerald-500" />
                            <span>Target tercapai! Bonus menanti.</span>
                        @elseif($this->performance['persen'] >= 50)
                            <x-heroicon-o-fire class="w-3 h-3 text-orange-500" />
                            <span>Sedikit lagi, gas terus!</span>
                        @else
                            <x-heroicon-o-exclamation-triangle class="w-3 h-3 text-yellow-500" />
                            <span>Ayo kejar targetmu!</span>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-white/5 space-y-3">
    
                    <div class="flex justify-between items-center border-b border-gray-100 dark:border-white/5 pb-2">
                        <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-funnel class="w-4 h-4 text-emerald-500" />
                            Funnel {{ Carbon::parse($selectedMonth)->format('M Y') }}
                        </h3>
                        <input type="month" wire:model.live="selectedMonth" class="text-xs p-1 border-none bg-transparent text-right font-bold text-gray-500 dark:text-zinc-400 focus:ring-0">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-2 bg-emerald-50 dark:bg-emerald-900/10 rounded-xl">
                            <p class="text-[10px] text-gray-500 dark:text-emerald-300 uppercase font-bold">Conversion Rate</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $this->leadStats['ratio'] }}%</p>
                            <p class="text-[10px] text-gray-400">{{ $this->leadStats['converted'] }} Deal / {{ $this->leadStats['total'] }} Leads</p>
                        </div>

                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="flex items-center gap-1 text-gray-500"><div class="w-2 h-2 rounded-full bg-red-500"></div> Hot</span>
                                <span class="font-bold dark:text-white">{{ $this->leadStats['breakdown']['hot'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="flex items-center gap-1 text-gray-500"><div class="w-2 h-2 rounded-full bg-yellow-500"></div> Warm</span>
                                <span class="font-bold dark:text-white">{{ $this->leadStats['breakdown']['warm'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="flex items-center gap-1 text-gray-500"><div class="w-2 h-2 rounded-full bg-blue-500"></div> Cold</span>
                                <span class="font-bold dark:text-white">{{ $this->leadStats['breakdown']['cold'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button wire:click="resetForm" @click="showLeadModal = true" class="bg-emerald-600 hover:bg-emerald-700 text-white p-3 rounded-xl flex items-center justify-center gap-2 font-bold shadow-lg shadow-emerald-500/20 active:scale-95 transition">
                        <x-heroicon-o-user-plus class="w-5 h-5" />
                        Tambah Lead
                    </button>
                    <button @click="showReportModal = true" class="bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-xl flex items-center justify-center gap-2 font-bold shadow-lg shadow-orange-500/20 active:scale-95 transition">
                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                        Lapor Kegiatan
                    </button>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-gray-800 dark:text-white">SOP Hari Ini</h3>
                        <button wire:click="setTab('tasks')" class="text-xs text-emerald-600 font-bold">Lihat Semua</button>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($this->myTasks->take(3) as $task)
                        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-gray-100 dark:border-white/5 shadow-sm flex items-center gap-3">
                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-sm text-gray-800 dark:text-white line-clamp-1">{{ $task->title }}</h4>
                                <p class="text-xs text-gray-500">{{ $task->due_date->format('H:i') }} • {{ $task->template->frequency ?? 'Harian' }}</p>
                            </div>
                            
                            <button wire:click="executeTask({{ $task->id }})" 
                                class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm hover:bg-emerald-700 active:scale-95 transition">
                                Kerjakan
                            </button>
                        </div>
                        @empty
                        <div class="text-center p-4 bg-gray-50 dark:bg-zinc-900 rounded-xl text-gray-400 dark:text-zinc-500 text-sm border border-dashed border-gray-200 dark:border-zinc-700">
                            Tidak ada tugas SOP hari ini. Aman! 👍
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'leads')
        <div class="space-y-4 animate-fade-in">
            
            <div class="sticky top-0 bg-gray-50 dark:bg-zinc-950 pt-1 pb-4 z-10 space-y-3">
                
                <div class="bg-gray-200 dark:bg-zinc-800 p-1 rounded-xl flex font-bold text-[12px] sm:text-xs shadow-inner">
                    <button wire:click="$set('leadType', 'personal')" class="flex-1 py-2 rounded-lg transition {{ $leadType === 'personal' ? 'bg-white dark:bg-zinc-700 text-emerald-600 shadow-sm' : 'text-gray-500 dark:text-zinc-400' }}">
                        Personal
                    </button>
                    <button wire:click="$set('leadType', 'corporate')" class="flex-1 py-2 rounded-lg transition {{ $leadType === 'corporate' ? 'bg-white dark:bg-zinc-700 text-emerald-600 shadow-sm' : 'text-gray-500 dark:text-zinc-400' }}">
                        Korporat
                    </button>
                    <button wire:click="$set('leadType', 'converted')" class="flex-1 py-2 rounded-lg transition {{ $leadType === 'converted' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-500 dark:text-zinc-400' }}">
                        Closing
                    </button>

                </div>
                

                @if($leadType === 'converted')
                    <div class="flex justify-between items-center px-1">
                        <span class="text-xs font-bold text-gray-500">Histori Closing:</span>
                        <input type="month" wire:model.live="selectedMonth" class="text-sm p-1.5 rounded-lg border-gray-300 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white font-bold">
                    </div>
                @else
                    <div class="relative">
                        <input wire:model.live.debounce.500ms="searchLead" type="text" placeholder="Cari prospek..." 
                            class="w-full pl-10 rounded-xl border-gray-300 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white shadow-sm focus:ring-emerald-500 focus:border-emerald-500 p-3">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" />
                    </div>
                    
                    <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                        @foreach(['new'=>'Baru', 'contacted'=>'Hubungi', 'warm'=>'Prospek', 'hot'=>'Hot'] as $key => $label)
                        <button wire:click="$set('filterStatus', '{{ $filterStatus == $key ? '' : $key }}')" 
                            class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition border {{ $filterStatus == $key ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-zinc-900 text-gray-600 dark:text-zinc-400 border-gray-200 dark:border-zinc-700' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                @endif
                
            </div>

            @forelse($this->leads as $lead)
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-white/5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 px-3 py-1 rounded-bl-xl text-[10px] font-bold uppercase 
                    {{ match($lead->status) { 
                        'converted' => 'bg-green-600 text-white',
                        'hot'=>'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400', 
                        'warm'=>'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400', 
                        default=>'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'} 
                    }}">
                    {{ $lead->status == 'converted' ? 'DEAL ✅' : $lead->status }}
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-zinc-800 flex items-center justify-center text-lg font-bold text-gray-500 dark:text-gray-300">
                        {{ substr($leadType !== 'corporate' ? $lead->name : $lead->company_name, 0, 1) }}
                    </div>
                    
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 dark:text-white text-lg">
                            {{ $leadType !== 'corporate' ? $lead->name : $lead->company_name }}
                        </h3>
                        
                        <p class="text-sm text-gray-500 dark:text-zinc-400">
                            @if($leadType !== 'corporate')
                                {{ $lead->city ?? '-' }} • {{ $lead->source }}
                            @else
                                PIC: {{ $lead->pic_name }} • Pax: {{ $lead->potential_pax }}
                            @endif
                        </p>
                        
                        @if($leadType !== 'corporate' && $lead->potential_package)
                        <div class="mt-2 inline-block bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 text-[10px] px-2 py-1 rounded border border-purple-100 dark:border-purple-800">
                            Minat: {{ $lead->potential_package }}
                        </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-white/5 space-y-2">

                    <div class="flex gap-2">
                        @php
                            $phone = $leadType == 'personal' ? $lead->phone : $lead->pic_phone;
                        @endphp

                        <a
                            href="https://wa.me/{{ preg_replace('/^0/', '62', $phone) }}"
                            target="_blank"
                            class="flex-1 h-10 bg-green-500 hover:bg-green-600
                                text-white rounded-lg text-sm font-bold
                                flex items-center justify-center gap-2
                                shadow-sm transition"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            WhatsApp
                        </a>

                        <button
                            wire:click="openEditLead({{ $lead->id }})"
                            class="flex-1 h-10 bg-zinc-200 dark:bg-zinc-800
                                text-zinc-600 dark:text-gray-300
                                rounded-lg text-sm font-bold
                                flex items-center justify-center
                                hover:bg-zinc-300 dark:hover:bg-zinc-700
                                transition"
                        >
                            Detail
                        </button>
                    </div>

                    @if(in_array($lead->status, ['hot', 'closing', 'deal']))
                        <button
                            wire:click="openConvertModal({{ $lead->id }})"
                            class="w-full h-11
                                bg-gradient-to-r from-blue-600 to-indigo-600
                                text-white rounded-lg text-sm font-bold
                                shadow-md shadow-blue-500/30
                                flex items-center justify-center gap-2
                                hover:scale-[1.02] transition"
                        >
                            <x-heroicon-m-user-plus class="w-5 h-5" />
                            Convert to Jamaah
                        </button>
                    @else
                        <div class="w-full text-center text-xs text-green-600 font-bold bg-green-50 dark:bg-green-900/20 p-2 rounded-lg">
                            Closing pada {{ $lead->updated_at->format('d M Y') }}
                        </div>
                    @endif

                </div>

            </div>
            @empty
                <div class="text-center p-10 mt-10">
                    <div class="bg-gray-100 dark:bg-zinc-800 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-heroicon-o-inbox class="w-8 h-8 text-gray-400" />
                    </div>
                    <h3 class="text-gray-800 dark:text-white font-bold">Belum ada Leads Aktif</h3>
                    <p class="text-gray-500 text-sm mt-1">Gunakan filter atau tambah prospek baru.</p>
                </div>
            @endforelse

            <div class="py-4">
                {{ $this->leads->links() }}
            </div>
        </div>
        @endif

        @if($activeTab === 'tasks')
        <div class="space-y-4 animate-fade-in">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white px-2">Daftar Tugas (SOP)</h2>
            
            @foreach($this->myTasks as $task)
            <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-gray-200 dark:border-white/5 shadow-sm">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $task->template->frequency ?? 'ADHOC' }}</span>
                        <h3 class="font-bold text-gray-800 dark:text-white">{{ $task->title }}</h3>
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $task->description }}</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 px-2 py-1 rounded text-xs font-bold ml-2 whitespace-nowrap">
                        {{ $task->due_date->format('d M') }}
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-white/5">
    
                    @if($task->due_date < now())
                        <button disabled class="w-full bg-red-50 text-red-500 border border-red-100 dark:bg-red-900/10 dark:text-red-400 dark:border-red-900/20 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-2 cursor-not-allowed opacity-75">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4" />
                            Anda melewatkan tugas ini
                        </button>

                    @elseif($task->action_url || $task->template?->action_url)
                        <button wire:click="executeTask({{ $task->id }})" 
                            wire:loading.attr="disabled"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm text-center transition flex justify-center items-center gap-2 active:scale-95">
                            
                            <span wire:loading.remove target="executeTask({{ $task->id }})">Kerjakan Sekarang</span>
                            <x-heroicon-m-arrow-right wire:loading.remove target="executeTask({{ $task->id }})" class="w-4 h-4" />
                            
                            <span wire:loading target="executeTask({{ $task->id }})" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Memproses...
                            </span>
                        </button>

                    @else
                        <button disabled class="w-full bg-gray-100 text-gray-400 dark:bg-zinc-800 dark:text-zinc-600 px-4 py-2 rounded-lg text-sm font-bold cursor-not-allowed">
                            Tidak ada Link
                        </button>
                    @endif

                </div>
            </div>
            @endforeach

            @if($this->myTasks->isEmpty())
                <div class="text-center p-10 text-gray-400 dark:text-zinc-500 border border-dashed border-gray-200 dark:border-zinc-700 rounded-xl">
                    <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-emerald-500" />
                    Semua tugas beres! 🎉
                </div>
            @endif
        </div>
        @endif

        @if($activeTab === 'profile')
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-white/5 text-center">
            <div class="w-24 h-24 bg-emerald-100 dark:bg-emerald-900/30 rounded-full mx-auto mb-4 flex items-center justify-center text-4xl font-bold text-emerald-600 dark:text-emerald-400 border-4 border-white dark:border-zinc-800 shadow-md">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ Auth::user()->name }}</h2>
            <p class="text-gray-500 dark:text-zinc-400">Sales Executive</p>
            
            <div class="mt-8 space-y-3">
                <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-red-500 font-bold border border-red-200 dark:border-red-900/50 hover:bg-red-50 dark:hover:bg-red-900/20 px-4 py-3 rounded-xl transition flex items-center justify-center gap-2">
                        <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" />
                        Keluar Aplikasi
                    </button>
                </form>
            </div>
        </div>
        @endif

    </main>

    <nav class="fixed bottom-0 w-full bg-white dark:bg-zinc-900 border-t border-gray-200 dark:border-white/10 flex justify-around items-end pb-4 pt-2 z-40 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]"
        style="
            background-image: url('/images/ornaments/arabesque.png');
            background-repeat: repeat;
            background-size: 150px 150px;
        ">
        
        <button wire:click="setTab('home')" class="flex flex-col items-center gap-1 w-16 transition {{ $activeTab === 'home' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300' }}">
            <x-heroicon-o-home class="w-6 h-6" />
            <span class="text-[10px] font-bold">Home</span>
        </button>

        <button wire:click="setTab('leads')" class="flex flex-col items-center gap-1 w-16 transition {{ $activeTab === 'leads' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300' }}">
            <x-heroicon-o-user-group class="w-6 h-6" />
            <span class="text-[10px] font-bold">Leads</span>
        </button>

        <div class="relative -top-6">
            <button wire:click="resetForm" @click="showLeadModal = true" class="bg-emerald-600 text-white w-14 h-14 rounded-full shadow-lg shadow-emerald-600/30 border-4 border-gray-50 dark:border-zinc-950 flex items-center justify-center hover:scale-105 active:scale-95 transition">
                <x-heroicon-o-plus class="w-8 h-8" />
            </button>
        </div>

        <button wire:click="setTab('tasks')" class="flex flex-col items-center gap-1 w-16 transition {{ $activeTab === 'tasks' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300' }}">
            <x-heroicon-o-clipboard-document-check class="w-6 h-6" />
            <span class="text-[10px] font-bold">Tasks</span>
        </button>

        <button wire:click="setTab('profile')" class="flex flex-col items-center gap-1 w-16 transition {{ $activeTab === 'profile' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300' }}">
            <x-heroicon-o-user-circle class="w-6 h-6" />
            <span class="text-[10px] font-bold">Akun</span>
        </button>
    </nav>

    <div x-show="showLeadModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center px-4 py-6 sm:p-0" style="display: none;" x-transition.opacity>
        <div @click="showLeadModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="relative bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl transform transition-all w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto" x-transition.move.bottom>
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-zinc-800 pb-3">
                <h3 class="text-lg font-bold dark:text-white">
                    {{ $isEditing ? 'Edit / Detail Lead' : 'Tambah Lead ' . ucfirst($leadType) }}
                </h3>
                <button @click="showLeadModal = false" class="text-gray-400 hover:text-gray-600">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </button>
            </div>

            <div class="space-y-4">
        
                @if($leadType === 'personal')
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase">Informasi Dasar</label>
                        
                        <input wire:model="name" type="text" placeholder="Nama Calon *" class="w-full rounded-xl border-gray-300 bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                        
                        <div class="flex gap-2">
                            <div class="w-16 flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-xl text-gray-500 font-bold">+62</div>
                            <input wire:model="phone" type="number" placeholder="812xxxx (Tanpa 0) *" class="flex-1 rounded-xl border-gray-300 bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                        </div>

                        <input wire:model="city" type="text" placeholder="Kota Domisili" class="w-full rounded-xl border-gray-300 bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                        
                        <select wire:model.live="source" class="w-full rounded-xl border-gray-300 bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                            <option value="">-- Sumber Info * --</option>
                            <option value="Facebook Ads">Facebook Ads</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Tiktok">Tiktok</option>
                            <option value="Website">Website</option>
                            <option value="Agent">Referensi Agen</option>
                            <option value="Walk-in">Datang ke Kantor</option>
                            <option value="Referral">Referral Teman</option>
                        </select>
                    </div>

                    <div class="space-y-3 pt-2 border-t border-gray-100 dark:border-zinc-800">
                        <label class="block text-xs font-bold text-gray-500 uppercase">Detail & Status</label>

                        @if($source === 'Agent')
                        <div class="animate-fade-in">
                            <label class="text-xs text-gray-400 mb-1 block">Pilih Agen Referensi *</label>
                            <select wire:model="agent_id" class="w-full rounded-xl border-gray-300 bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-orange-500 font-bold">
                                <option value="">-- Pilih Agen --</option>
                                @foreach($this->agents as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Sales Handle</label>
    
                            <div class="relative">
                                <input type="text" 
                                    value="{{ auth()->user()->name }}" 
                                    disabled
                                    class="w-full rounded-xl border-gray-300 bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 p-3 font-bold cursor-not-allowed">
                                
                                <div class="absolute right-3 top-3.5 text-gray-400">
                                    <x-heroicon-m-lock-closed class="w-5 h-5" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Minat Paket Umrah</label>
                            <select wire:model="potential_package" class="w-full rounded-xl border-gray-300 bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">-- Pilih Paket Tersedia --</option>
                                
                                @foreach($this->packages as $packageName => $label)
                                    <option value="{{ $packageName }}">{{ $label }}</option>
                                @endforeach
                                
                                <option value="General">Belum Tahu (General)</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Status Prospek</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach([
                                    'cold' => ['label' => 'Cold', 'color' => 'bg-blue-100 text-blue-700 border-blue-200'],
                                    'warm' => ['label' => 'Warm', 'color' => 'bg-yellow-100 text-yellow-700 border-yellow-200'],
                                    'hot' => ['label' => 'Hot', 'color' => 'bg-red-100 text-red-700 border-red-200'],
                                    'closing' => ['label' => 'Deal', 'color' => 'bg-green-100 text-green-700 border-green-200']
                                ] as $val => $style)
                                <button type="button" wire:click="$set('status', '{{ $val }}')" 
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $status === $val ? $style['color'] . ' ring-2 ring-offset-1 ring-current' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                    {{ $style['label'] }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        <textarea wire:model="notes" rows="2" placeholder="Catatan Follow Up..." class="w-full rounded-xl border-gray-300 bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white"></textarea>
                    </div>

                @else
                    <div class="space-y-3">
                        <input wire:model="company_name" type="text" placeholder="Nama Perusahaan *" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                        <input wire:model="pic_name" type="text" placeholder="Nama PIC *" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                        <input wire:model="pic_phone" type="number" placeholder="Nomor WA PIC *" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                        <input wire:model="address" type="text" placeholder="Alamat Kantor" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                        <input wire:model="potential_pax" type="number" placeholder="Potensi Pax" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                        
                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Sales Handle *</label>
                            <select wire:model="sales_id" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                                @foreach($this->employees as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <textarea wire:model="notes" rows="2" placeholder="Catatan..." class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white"></textarea>
                    </div>
                @endif

                <div class="mt-6 flex gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <button @click="showLeadModal = false" wire:click="resetForm" class="flex-1 py-3 bg-zinc-200 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-xl font-bold hover:bg-zinc-300 transition">
                        Batal
                    </button>
                    <button wire:click="saveLead" wire:loading.attr="disabled" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-500/30 transition flex justify-center items-center gap-2">
                        <span wire:loading.remove>{{ $isEditing ? 'Update Data' : 'Simpan Lead' }}</span>
                        <span wire:loading>Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showReportModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center px-4 py-6 sm:p-0" style="display: none;" x-transition.opacity>
        <div @click="showReportModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="relative bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl transform transition-all w-full max-w-lg p-6" x-transition.move.bottom>
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-zinc-800 pb-3">
                <h3 class="text-lg font-bold dark:text-white">📝 Lapor Kegiatan</h3>
                <button @click="showReportModal = false" class="text-gray-400 hover:text-gray-600">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </button>
            </div>
            
            <div class="space-y-3">
                <select wire:model="report_type" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                    <option value="canvasing">Canvasing / Flyering</option>
                    <option value="meeting">Meeting Luar Kantor</option>
                    <option value="event">Jaga Booth / Event</option>
                    <option value="follow_up">Follow Up Umum</option>
                </select>
                
                <input wire:model="report_loc" type="text" placeholder="Lokasi (Misal: Masjid Al-Azhar)" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                
                <input wire:model="report_qty" type="number" placeholder="Jumlah Prospek Didapat (Est)" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white">
                
                <textarea wire:model="report_desc" rows="3" placeholder="Deskripsi hasil kegiatan..." class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 p-3 dark:text-white"></textarea>
            </div>

            <div class="mt-6 flex gap-3">
                <button @click="showReportModal = false" class="flex-1 py-3 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-xl font-bold hover:bg-gray-200 transition">Batal</button>
                <button wire:click="saveActivityReport" class="flex-1 py-3 bg-orange-500 text-white rounded-xl font-bold hover:bg-orange-600 shadow-lg shadow-orange-500/30 transition">Kirim Laporan</button>
            </div>
        </div>
    </div>

    <div x-data="{ showConvertModal: false }" 
        x-on:open-convert-modal.window="showConvertModal = true; $wire.set('convertStep', 1)"
        x-show="showConvertModal" 
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center px-4 py-6 sm:p-0" 
        style="display: none;" 
        x-transition.opacity>
        
        <div @click="showConvertModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="relative bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl transform transition-all w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto" x-transition.move.bottom>
            
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-zinc-800 pb-3">
                <h3 class="text-lg font-bold dark:text-white flex items-center gap-2">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shadow-sm">
                        {{ $convertStep }}
                    </span>
                    <span class="text-gray-800 dark:text-white">
                        @if($convertStep == 1) Pilih Mode Jamaah
                        @elseif($convertStep == 2) Data Jamaah
                        @else Booking & DP
                        @endif
                    </span>
                </h3>
                <button @click="showConvertModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </button>
            </div>

            @if($convertStep === 1)
            <div class="space-y-4 animate-fade-in">
                <p class="text-sm text-gray-500 dark:text-zinc-400">Apakah ini Jamaah Baru atau Repeat Order (RO)?</p>
                
                <div class="grid grid-cols-2 gap-3">
                    <button wire:click="$set('convertMode', 'new')" class="p-4 rounded-xl border-2 transition {{ $convertMode === 'new' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'border-gray-200 dark:border-zinc-700 text-gray-500 dark:text-zinc-400 hover:bg-gray-50 dark:hover:bg-zinc-800' }}">
                        <x-heroicon-m-user-plus class="w-8 h-8 mx-auto mb-2" />
                        <span class="font-bold block text-sm">Daftar Baru</span>
                    </button>
                    
                    <button wire:click="$set('convertMode', 'existing')" class="p-4 rounded-xl border-2 transition {{ $convertMode === 'existing' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'border-gray-200 dark:border-zinc-700 text-gray-500 dark:text-zinc-400 hover:bg-gray-50 dark:hover:bg-zinc-800' }}">
                        <x-heroicon-m-magnifying-glass class="w-8 h-8 mx-auto mb-2" />
                        <span class="font-bold block text-sm">Sudah Ada</span>
                    </button>
                </div>

                @if($convertMode === 'existing')
                <div class="mt-4 animate-fade-in">
                    <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-2 block">Cari Jamaah Lama</label>
                    <select wire:model="existing_jamaah_id" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Jamaah --</option>
                        @foreach(Jamaah::limit(20)->get() as $j)
                            <option value="{{ $j->id }}">{{ $j->name }} - {{ $j->nik }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <button wire:click="goToStep2" class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition flex justify-center items-center gap-2">
                        Lanjut Isi Data <x-heroicon-m-arrow-right class="w-4 h-4" />
                    </button>
                </div>
            </div>
            @endif

            @if($convertStep === 2)
            <div class="space-y-4 animate-fade-in">
                @if($convertMode === 'new')
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800 space-y-3">
                        <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">Akun Aplikasi</p>
                        <input wire:model="new_email" type="email" placeholder="Email Jamaah (Login) *" class="w-full rounded-lg border-blue-200 bg-blue-100/50 dark:bg-zinc-800 dark:border-blue-800/50 p-2 text-sm focus:ring-blue-500 dark:text-white placeholder-blue-300">
                        <input wire:model="new_password" type="password" placeholder="Password *" class="w-full rounded-lg border-blue-200 bg-blue-100/50 dark:bg-zinc-800 dark:border-blue-800/50 p-2 text-sm focus:ring-blue-500 dark:text-white placeholder-blue-300">
                    </div>

                    <div class="space-y-3">
                        <input wire:model="new_nik" type="number" placeholder="NIK KTP (16 Digit) *" class="w-full rounded-xl border-gray-300 bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3 focus:ring-blue-500 focus:border-blue-500">
                        <input wire:model="new_name" type="text" placeholder="Nama Lengkap *" class="w-full rounded-xl border-gray-300 bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3 focus:ring-blue-500 focus:border-blue-500">
                        
                        <div class="flex gap-2">
                            <select wire:model="new_gender" class="w-1/2 rounded-xl border-gray-300 bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Gender *</option>
                                <option value="pria">Pria</option>
                                <option value="wanita">Wanita</option>
                            </select>
                            <div class="flex-1 flex gap-1">
                                <div class="w-14 flex items-center justify-center bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-xl text-gray-500 dark:text-zinc-300 font-bold text-sm">+62</div>
                                <input wire:model="new_phone" type="number" placeholder="No WA *" class="flex-1 rounded-xl border-gray-300 bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <textarea wire:model="new_address" rows="2" placeholder="Alamat Domisili" class="w-full rounded-xl border-gray-300 bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        
                        <div x-data="{ openDoc: false }" class="pt-2">
                            <button @click="openDoc = !openDoc" class="text-xs font-bold text-gray-500 dark:text-zinc-400 flex items-center gap-1 hover:text-blue-500 transition">
                                <span x-text="openDoc ? 'Tutup Detail Dokumen' : 'Isi Detail Dokumen (Paspor, dll)'"></span>
                                <x-heroicon-m-chevron-down class="w-3 h-3 transition-transform" x-bind:class="openDoc ? 'rotate-180' : ''" />
                            </button>
                            
                            <div x-show="openDoc" class="mt-3 space-y-3 pt-3 border-t border-gray-100 dark:border-zinc-800 animate-fade-in" style="display: none;">
                                <input wire:model="new_passport_number" type="text" placeholder="Nomor Paspor" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3">
                                <div class="flex gap-2">
                                    <div class="w-1/2">
                                        <label class="text-[10px] text-gray-400 mb-1 block uppercase font-bold">Paspor Exp</label>
                                        <input wire:model="new_passport_expiry" type="date" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3">
                                    </div>
                                    <div class="w-1/2">
                                        <label class="text-[10px] text-gray-400 mb-1 block uppercase font-bold">Baju</label>
                                        <input wire:model="new_shirt_size" type="text" placeholder="S/M/L/XL" class="w-full rounded-xl border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white p-3 text-center uppercase font-bold">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-xl text-center border border-green-200 dark:border-green-800">
                        <p class="font-bold text-lg mb-1">Data Jamaah Terpilih ✅</p>
                        <p class="text-sm opacity-80">Klik lanjut untuk membuat booking.</p>
                    </div>
                @endif

                <div class="mt-6 flex gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <button wire:click="$set('convertStep', 1)" class="w-1/3 py-3 bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-zinc-700 transition">Kembali</button>
                    <button wire:click="goToStep3" class="w-2/3 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition flex justify-center items-center gap-2">
                        Lanjut Booking <x-heroicon-m-arrow-right class="w-4 h-4" />
                    </button>
                </div>
            </div>
            @endif

            @if($convertStep === 3)
            <div class="space-y-6 animate-fade-in pb-4">
                
                <div class="space-y-2">
                    <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase">Pilih Paket Umrah</label>
                    <select wire:model.live="booking_package_id" class="w-full rounded-xl border-blue-300 bg-blue-50 text-blue-900 font-bold p-3 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-900 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Paket --</option>
                        @foreach($this->packagesList as $pkg)
                            <option value="{{ $pkg->id }}">{{ $pkg->name }} ({{ Carbon::parse($pkg->departure_date)->format('d M') }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-gray-50 dark:bg-zinc-800 p-4 rounded-xl flex justify-between items-center border border-gray-100 dark:border-zinc-700 shadow-sm">
                    <span class="text-sm text-gray-500 dark:text-zinc-400">Total Harga</span>
                    <span class="text-lg font-black text-gray-800 dark:text-white">Rp {{ number_format($booking_price, 0, ',', '.') }}</span>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-1 bg-emerald-500 rounded-full"></div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Pembayaran DP</h3>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Nominal DP (Rp)</label>
                        <input wire:model="dp_amount" type="number" class="w-full text-2xl font-bold text-right p-3 border-2 border-emerald-500 rounded-xl focus:ring-4 focus:ring-emerald-200 text-emerald-800 dark:bg-zinc-800 dark:text-white dark:border-emerald-600 dark:focus:ring-emerald-900" placeholder="0">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <label class="cursor-pointer group">
                            <input type="radio" wire:model.live="dp_method" value="cash" class="peer sr-only">
                            <div class="p-4 border-2 rounded-xl text-center font-bold text-gray-500 dark:text-zinc-400 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-900/30 dark:peer-checked:text-emerald-400 transition group-hover:bg-gray-50 dark:group-hover:bg-zinc-800 flex flex-col items-center gap-2">
                                <x-heroicon-o-banknotes class="w-8 h-8" />
                                <span>Tunai (Cash)</span>
                            </div>
                        </label>

                        <label class="cursor-pointer group">
                            <input type="radio" wire:model.live="dp_method" value="transfer" class="peer sr-only">
                            <div class="p-4 border-2 rounded-xl text-center font-bold text-gray-500 dark:text-zinc-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 dark:peer-checked:bg-blue-900/30 dark:peer-checked:text-blue-400 transition group-hover:bg-gray-50 dark:group-hover:bg-zinc-800 flex flex-col items-center gap-2">
                                <x-heroicon-o-building-library class="w-8 h-8" />
                                <span>Transfer Bank</span>
                            </div>
                        </label>
                    </div>

                    @if($dp_method)
                    <div class="animate-fade-in">
                        <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">
                            {{ $dp_method == 'cash' ? 'Masuk ke Kas:' : 'Transfer ke Rekening:' }}
                        </label>
                        <select wire:model="target_wallet_id" class="w-full p-3 border-2 border-gray-200 rounded-xl bg-white dark:bg-zinc-800 dark:border-zinc-700 dark:text-white font-bold">
                            <option value="">-- Pilih Tujuan --</option>
                            @foreach($this->wallets as $wallet)
                                @if($dp_method == 'cash' && $wallet->type == 'cashier')
                                    <option value="{{ $wallet->id }}">{{ $wallet->name }}</option>
                                @elseif($dp_method == 'transfer' && $wallet->type == 'bank')
                                    <option value="{{ $wallet->id }}">{{ $wallet->name }} ({{ $wallet->account_number }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="bg-gray-50 dark:bg-zinc-800 p-3 rounded-xl border border-dashed border-gray-300 dark:border-zinc-600">
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Bukti Transaksi</label>
                        <input wire:model="dp_proof" type="file" accept="image/*" class="w-full text-xs text-gray-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300 hover:file:bg-emerald-100 transition cursor-pointer">
                        @error('dp_proof') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <button wire:click="$set('convertStep', 2)" class="w-1/3 py-3 bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-zinc-700 transition">Kembali</button>
                    <button wire:click="processFinalConversion" wire:loading.attr="disabled" class="w-2/3 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-500/30 flex justify-center gap-2 transition transform active:scale-95">
                        <span wire:loading.remove>BOOKING SEKARANG</span>
                        <span wire:loading>Tunggu...</span>
                    </button>
                </div>
            </div>
            @endif

        </div>
    </div>

    <div x-show="showMediaModal" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" style="display: none;" x-transition.opacity>
    
            <div @click="showMediaModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

            <div class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]" x-transition.move.bottom>
                
                <div class="bg-white dark:bg-zinc-900 border-b border-gray-100 dark:border-white/5 p-4 flex justify-between items-center shrink-0">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-m-swatch class="w-5 h-5 text-indigo-500" />
                        Creative Support
                    </h3>
                    <button @click="showMediaModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="flex p-2 bg-gray-50 dark:bg-zinc-950/50 shrink-0">
                    <button wire:click="$set('mediaTab', 'upload')" 
                        class="flex-1 py-2 rounded-lg text-sm font-bold transition flex justify-center items-center gap-2 
                        {{ $mediaTab === 'upload' ? 'bg-white dark:bg-zinc-800 shadow text-indigo-600' : 'text-gray-500 hover:bg-gray-200 dark:hover:bg-zinc-800' }}">
                        <x-heroicon-m-arrow-up-tray class="w-4 h-4" /> Upload Aset
                    </button>
                    <button wire:click="$set('mediaTab', 'request')" 
                        class="flex-1 py-2 rounded-lg text-sm font-bold transition flex justify-center items-center gap-2 
                        {{ $mediaTab === 'request' ? 'bg-white dark:bg-zinc-800 shadow text-indigo-600' : 'text-gray-500 hover:bg-gray-200 dark:hover:bg-zinc-800' }}">
                        <x-heroicon-m-pencil-square class="w-4 h-4" /> Request Desain
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar">
                    
                    @if($mediaTab === 'upload')
                    <div class="space-y-5">
                        
                        <div class="bg-indigo-50 dark:bg-indigo-900/10 p-4 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                            <label class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase mb-2 block flex items-center gap-1">
                                <x-heroicon-o-folder class="w-3 h-3" />
                                Simpan Ke Folder:
                            </label>
                            <select wire:model.live="selectedPackageId" class="...">
                                <option value="">-- Folder Umum / Non-Grup --</option>
                                
                                @foreach($this->mediaPackages as $pkg) 
                                    <option value="{{ $pkg->id }}">
                                        {{ Str::limit($pkg->name, 40) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border-2 border-dashed border-gray-300 dark:border-zinc-700 rounded-xl p-8 text-center hover:border-indigo-500 transition relative group bg-gray-50 dark:bg-zinc-800/30 cursor-pointer">
                            <input type="file" wire:model="mediaPhotos" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            
                            <div class="space-y-3 pointer-events-none">
                                <div wire:loading wire:target="mediaPhotos">
                                    <div class="mx-auto w-12 h-12 flex items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 mb-2">
                                        <x-heroicon-o-arrow-path class="w-6 h-6 text-indigo-600 animate-spin" />
                                    </div>
                                    <p class="text-xs text-indigo-600 font-bold animate-pulse">Sedang mengupload...</p>
                                </div>

                                <div wire:loading.remove wire:target="mediaPhotos">
                                    <div class="mx-auto w-12 h-12 flex items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 mb-2 group-hover:scale-110 transition group-hover:bg-indigo-100">
                                        <x-heroicon-o-camera class="w-6 h-6 text-indigo-500" />
                                    </div>
                                    <p class="text-sm font-bold text-gray-700 dark:text-zinc-200">
                                        Tap untuk Ambil Foto / Pilih File
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Bisa pilih banyak sekaligus
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">
                                Label / Keterangan (Opsional)
                            </label>
                            <input wire:model="mediaTags" type="text" placeholder="Contoh: Bukti Transfer, Invoice Hotel..." 
                                class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white text-sm p-3 focus:ring-indigo-500 focus:border-indigo-500 placeholder:text-gray-400">
                        </div>

                        <button wire:click="saveMediaAssets" wire:loading.attr="disabled" 
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-500/20 flex justify-center items-center gap-2 transition transform active:scale-[0.98]">
                            <span wire:loading.remove wire:target="saveMediaAssets">
                                <x-heroicon-m-cloud-arrow-up class="w-5 h-5 inline" />
                                Simpan File
                            </span>
                            <span wire:loading wire:target="saveMediaAssets">
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                    @endif

                    @if($mediaTab === 'request')
                    <div class="space-y-5">
                        
                        <div>
                            <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Judul Request</label>
                            <input wire:model="reqTitle" type="text" placeholder="Ct: Desain Flyer Promo Akhir Tahun" 
                                class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white text-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Detail Kebutuhan</label>
                            <textarea wire:model="reqDesc" rows="4" placeholder="Jelaskan warna, teks, ukuran, atau referensi..." 
                                class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white text-sm p-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Deadline</label>
                                <input wire:model="reqDeadline" type="date" 
                                    class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white text-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1 block">Prioritas</label>
                                <select wire:model="reqPriority" class="w-full rounded-lg border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white text-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="low">Santai (Low)</option>
                                    <option value="medium">Standar (Medium)</option>
                                    <option value="high">🔥 Urgent (High)</option>
                                </select>
                            </div>
                        </div>

                        <button wire:click="saveContentRequest" wire:loading.attr="disabled"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-500/20 mt-2 flex justify-center items-center gap-2 transition transform active:scale-[0.98]">
                            <span wire:loading.remove wire:target="saveContentRequest">
                                <x-heroicon-m-paper-airplane class="w-5 h-5 inline" />
                                Kirim Request
                            </span>
                            <span wire:loading wire:target="saveContentRequest">
                                Mengirim...
                            </span>
                        </button>
                    </div>
                    @endif

                </div>
            </div>
        </div>

</div>
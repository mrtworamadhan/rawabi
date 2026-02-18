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

<div class="flex flex-col h-full w-full relative bg-slate-50 dark:bg-[#09090b]" 
     x-data="{ 
        mobileMenuOpen: false,
        showLeadModal: false,
        showReportModal: false,
        showConvertModal: false,
        showMediaModal: @entangle('showMediaModal')
     }"
     x-on:close-modal.window="showLeadModal = false; showReportModal = false; showConvertModal = false"
     x-on:open-add-lead-modal.window="showLeadModal = true"
     x-on:open-convert-modal.window="showConvertModal = true; $wire.set('convertStep', 1)">

    <div class="absolute -bottom-24 -right-24 w-128 h-128 opacity-40 dark:opacity-40 pointer-events-none transform">
        <img src="{{ asset('images/icons/kabah1.png') }}" alt="Kabah Decoration" class="w-full h-full object-contain">
    </div>

    <nav class="w-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md px-4 py-3 flex justify-between items-center border-b border-slate-200 dark:border-white/5 shrink-0 z-50 sticky top-0 md:relative"
        style="
            background-image: url('/images/ornaments/arabesque.png');
            background-repeat: repeat;
            background-size: 150px 150px;
        ">

        <div class="flex items-center gap-3">
            <div class="w-9 h-9 md:w-10 md:h-10 bg-gradient-to-br from-indigo-600 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 shrink-0">
                <x-heroicon-s-presentation-chart-line class="w-5 h-5 md:w-6 md:h-6" />
            </div>
            
            <div class="flex flex-col">
                <span class="font-black text-sm md:text-base tracking-tight leading-none uppercase text-slate-900 dark:text-white">
                    Marketing <span class="text-indigo-600 dark:text-indigo-400">Center</span>
                </span>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[9px] md:text-[10px] font-bold text-slate-400 dark:text-zinc-500 tracking-widest uppercase">
                        Sales Force
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 md:gap-4">
            <button @click="toggleTheme()" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800 transition">
                <x-heroicon-s-moon class="w-5 h-5" x-show="!darkMode" />
                <x-heroicon-s-sun class="w-5 h-5 text-yellow-500" x-show="darkMode" x-cloak />
            </button>

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center gap-2 p-1 pr-1 md:pr-3 rounded-full bg-slate-100 dark:bg-zinc-800 hover:ring-2 hover:ring-indigo-500/30 transition-all cursor-pointer">
                    <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-black text-xs shadow-sm">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <span class="text-xs font-bold hidden md:block text-slate-700 dark:text-zinc-200">
                        {{ explode(' ', auth()->user()->name ?? 'User')[0] }}
                    </span>
                </button>

                <div x-show="open" 
                     @click.outside="open = false" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     class="absolute right-0 mt-3 w-56 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-white/5 py-2 z-[60] origin-top-right overflow-hidden" 
                     style="display: none;" 
                     x-cloak>
                    
                    <div class="px-4 py-3 bg-slate-50 dark:bg-white/5 mb-2">
                        <p class="text-xs font-black text-slate-900 dark:text-white truncate uppercase tracking-wider">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                    
                    <a href="/admin" class="group flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-white transition-all">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-zinc-800 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-all">
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
            
            <button wire:click="setTab('home')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'home' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'home' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-indigo-500/10' }}">
                    <x-heroicon-s-home class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Home</span>
                @if($activeTab === 'home') <div class="absolute -right-[25px] w-1.5 h-8 bg-indigo-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="setTab('leads')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'leads' ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'leads' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-blue-500/10' }}">
                    <x-heroicon-s-user-group class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Leads</span>
                @if($activeTab === 'leads') <div class="absolute -right-[25px] w-1.5 h-8 bg-blue-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="setTab('tasks')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'tasks' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'tasks' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-emerald-500/10' }}">
                    <x-heroicon-s-clipboard-document-check class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Tasks</span>
                @if($activeTab === 'tasks') <div class="absolute -right-[25px] w-1.5 h-8 bg-emerald-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="setTab('profile')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'profile' ? 'text-slate-800 dark:text-white' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'profile' ? 'bg-slate-800 text-white shadow-lg' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-slate-200' }}">
                    <x-heroicon-s-user-circle class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Akun</span>
            </button>
            <div class="absolute -bottom-18 -left-24 w-48 h-48 opacity-15 pointer-events-none z-0">
                <img src="{{ asset('images/ornaments/ornamen1.png') }}" 
                    alt="Ornamen" 
                    class="w-full h-full object-contain transform rotate-90">
            </div>

        </aside>

        <main class="flex-1 h-full overflow-y-auto custom-scrollbar px-4 md:px-8 pb-28 md:pb-8 pt-0 relative">
            
            <div class="mb-8 sticky top-0 bg-slate-50/90 dark:bg-zinc-950/90 backdrop-blur-md z-20 py-4 -mx-4 px-4 md:-mx-8 md:px-8 border-b border-transparent transition-all mt-4"
                 :class="{ 'border-slate-200 dark:border-white/5 shadow-sm !mt-0 !pt-4': $el.closest('main').scrollTop > 0 }">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-2xl transition-colors shadow-sm
                            @if($activeTab == 'home') 
                            bg-indigo-500/10
                            @elseif($activeTab == 'leads') 
                            bg-blue-500/10
                            @elseif($activeTab == 'tasks') 
                            bg-emerald-500/10
                            @else 
                            bg-slate-500/10
                            @endif 
                            ">
                            
                            @if($activeTab == 'home') 
                            <x-heroicon-s-home class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                            @elseif($activeTab == 'leads') 
                            <x-heroicon-s-user-group class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            @elseif($activeTab == 'tasks') 
                            <x-heroicon-s-clipboard-document-check class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                            @else 
                            <x-heroicon-s-user-circle class="w-6 h-6 text-slate-600 dark:text-slate-400" />
                            @endif
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                                @if($activeTab == 'home') Dashboard
                                @elseif($activeTab == 'leads') Database Leads
                                @elseif($activeTab == 'tasks') Daily Tasks
                                @elseif($activeTab == 'profile') Profil Sales
                                @endif
                            </h1>
                            <p class="text-[10px] md:text-xs text-slate-500 dark:text-zinc-500 font-bold uppercase tracking-widest">
                                Marketing & Sales Performance
                            </p>
                        </div>
                    </div>

                    @if($activeTab === 'home')
                        <div class="flex gap-3">
                            <button wire:click="resetForm" @click="showLeadModal = true" class="hidden md:flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-black transition shadow-lg shadow-indigo-500/30 active:scale-95">
                                <x-heroicon-m-user-plus class="w-5 h-5" /> Tambah Lead
                            </button>
                            <button @click="showReportModal = true" class="hidden md:flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-xl text-sm font-black transition shadow-lg shadow-orange-500/30 active:scale-95">
                                <x-heroicon-m-pencil-square class="w-5 h-5" /> Lapor
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            @if($activeTab === 'home')
            <div class="space-y-6 animate-fade-in">
                
                <div class="bg-gradient-to-br from-emerald-600 to-teal-500 rounded-[2rem] p-6 text-white shadow-xl shadow-emerald-500/20 relative overflow-hidden">
                    <div class="relative z-10">
                        <h2 class="text-2xl font-black tracking-tight">Semangat Pagi! 🚀</h2>
                        <p class="text-emerald-50 text-sm font-medium mt-1 mb-4">Kejar targetmu hari ini & cetak rekor baru.</p>
                        
                        <div class="inline-flex bg-white/20 backdrop-blur-md rounded-lg p-1">
                            <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white">
                                {{ Carbon::now()->format('l, d M Y') }}
                            </span>
                        </div>
                    </div>
                    <x-heroicon-o-trophy class="absolute -right-4 -bottom-4 w-32 h-32 text-white/10 rotate-12" />
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-white/5 flex items-center justify-between gap-4 relative overflow-hidden group cursor-pointer hover:border-indigo-200 transition-colors">
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300">
                            <x-heroicon-s-swatch class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white text-sm">Creative Support</h3>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 font-medium">Butuh konten promo?</p>
                        </div>
                    </div>

                    <button wire:click="$set('showMediaModal', true)" 
                        class="relative z-10 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wide shadow-lg shadow-indigo-500/30 active:scale-95 transition flex items-center gap-2">
                        <x-heroicon-s-camera class="w-4 h-4" />
                        <span>Tools</span>
                    </button>
                    
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-transparent to-indigo-50/50 dark:to-indigo-900/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-white/5 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">
                                Target • {{ $this->performance['period'] }}
                            </p>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white">Closingan Bulan Ini</h3>
                        </div>
                        <div class="px-3 py-1.5 rounded-xl text-xs font-black {{ explode(' ', $this->performance['color'])[0] }} bg-opacity-10 bg-current border border-current border-opacity-20">
                            {{ $this->performance['persen'] }}%
                        </div>
                    </div>

                    <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-5xl font-black text-slate-900 dark:text-white tracking-tighter">{{ $this->performance['realisasi'] }}</span>
                        <span class="text-slate-400 text-sm font-bold">/ {{ $this->performance['target'] }} Pax</span>
                    </div>

                    <div class="w-full bg-slate-100 dark:bg-zinc-800 rounded-full h-4 overflow-hidden shadow-inner">
                        <div class="h-full rounded-full transition-all duration-1000 ease-out relative {{ explode(' ', $this->performance['color'])[1] }}" 
                            style="width: {{ min($this->performance['persen'], 100) }}%">
                            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-slate-50 dark:bg-zinc-800/50 rounded-xl flex items-center gap-3">
                        @if($this->performance['persen'] >= 100)
                            <div class="p-1.5 bg-emerald-100 rounded-lg text-emerald-600"><x-heroicon-s-sparkles class="w-4 h-4" /></div>
                            <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Luar biasa! Target tercapai 🎯</p>
                        @elseif($this->performance['persen'] >= 50)
                            <div class="p-1.5 bg-orange-100 rounded-lg text-orange-600"><x-heroicon-s-fire class="w-4 h-4" /></div>
                            <p class="text-xs font-bold text-orange-600 dark:text-orange-400">Sedikit lagi, gas terus! 🔥</p>
                        @else
                            <div class="p-1.5 bg-slate-200 rounded-lg text-slate-600"><x-heroicon-s-chart-bar class="w-4 h-4" /></div>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400">Ayo mulai kejar angkanya!</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-white/5">
                    
                    <div class="flex justify-between items-center border-b border-slate-100 dark:border-white/5 pb-4 mb-4">
                        <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 text-sm uppercase tracking-wide">
                            <x-heroicon-s-funnel class="w-4 h-4 text-indigo-500" />
                            Sales Funnel
                        </h3>
                        <input type="month" wire:model.live="selectedMonth" 
                            class="text-xs font-bold bg-slate-50 dark:bg-zinc-800 border-none rounded-lg text-slate-600 dark:text-zinc-300 focus:ring-0 cursor-pointer">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col justify-center items-center p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100 dark:border-indigo-800/30">
                            <p class="text-[10px] text-indigo-400 dark:text-indigo-300 uppercase font-black tracking-widest mb-1">Win Rate</p>
                            <p class="text-3xl font-black text-indigo-600 dark:text-indigo-400">{{ $this->leadStats['ratio'] }}<span class="text-lg">%</span></p>
                            <div class="mt-2 text-[10px] font-bold text-indigo-400 bg-white dark:bg-white/5 px-2 py-1 rounded-md">
                                {{ $this->leadStats['converted'] }} Deal / {{ $this->leadStats['total'] }} Leads
                            </div>
                        </div>

                        <div class="flex flex-col justify-center gap-3">
                            <div class="flex justify-between items-center">
                                <span class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-zinc-400">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span> Hot
                                </span>
                                <span class="font-black text-slate-800 dark:text-white">{{ $this->leadStats['breakdown']['hot'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-zinc-400">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span> Warm
                                </span>
                                <span class="font-black text-slate-800 dark:text-white">{{ $this->leadStats['breakdown']['warm'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-zinc-400">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-sm shadow-blue-500/50"></span> Cold
                                </span>
                                <span class="font-black text-slate-800 dark:text-white">{{ $this->leadStats['breakdown']['cold'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button wire:click="resetForm" @click="showLeadModal = true" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white p-4 rounded-2xl flex flex-col items-center justify-center gap-2 font-bold shadow-lg shadow-indigo-500/30 active:scale-95 transition group">
                        <div class="p-2 bg-white/20 rounded-full group-hover:scale-110 transition">
                            <x-heroicon-s-user-plus class="w-6 h-6" />
                        </div>
                        <span class="text-xs uppercase tracking-wider">Lead Baru</span>
                    </button>
                    
                    <button @click="showReportModal = true" 
                        class="bg-orange-500 hover:bg-orange-600 text-white p-4 rounded-2xl flex flex-col items-center justify-center gap-2 font-bold shadow-lg shadow-orange-500/30 active:scale-95 transition group">
                        <div class="p-2 bg-white/20 rounded-full group-hover:scale-110 transition">
                            <x-heroicon-s-pencil-square class="w-6 h-6" />
                        </div>
                        <span class="text-xs uppercase tracking-wider">Lapor Giat</span>
                    </button>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-white/5">
                    <div class="flex justify-between items-center mb-5">
                        <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-tight text-sm flex items-center gap-2">
                            <x-heroicon-s-clipboard-document-list class="w-5 h-5 text-emerald-500" />
                            SOP Hari Ini
                        </h3>
                        <button wire:click="setTab('tasks')" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-700 bg-indigo-50 px-3 py-1 rounded-lg">Lihat Semua</button>
                    </div>
                    
                    <div class="space-y-4">
                        @forelse($this->myTasks->take(3) as $task)
                        <div class="group relative bg-slate-50 dark:bg-zinc-800/50 p-4 rounded-2xl border border-slate-100 dark:border-white/5 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors">
                            
                            <div class="flex justify-between items-start mb-2">
                                <span class="px-2 py-1 rounded-md bg-white dark:bg-zinc-800 text-[9px] font-bold text-slate-400 uppercase tracking-wide border border-slate-100 dark:border-white/5">
                                    {{ $task->template->frequency ?? 'Harian' }}
                                </span>
                                <span class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                                    <x-heroicon-m-clock class="w-3 h-3" /> {{ $task->due_date->format('H:i') }}
                                </span>
                            </div>

                            <h4 class="font-bold text-sm text-slate-800 dark:text-white line-clamp-1 mb-4">{{ $task->title }}</h4>
                            
                            @if($task->due_date < now())
                                <button disabled class="w-full py-2 bg-red-100 text-red-600 rounded-xl text-xs font-bold uppercase tracking-wide opacity-60 cursor-not-allowed">
                                    Terlewat
                                </button>
                            @else
                                <button wire:click="executeTask({{ $task->id }})" 
                                    class="w-full py-2 bg-white dark:bg-zinc-700 text-slate-700 dark:text-white border border-slate-200 dark:border-zinc-600 rounded-xl text-xs font-bold uppercase tracking-wide shadow-sm hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition">
                                    Kerjakan
                                </button>
                            @endif
                        </div>
                        @empty
                        <div class="py-8 text-center bg-slate-50 dark:bg-zinc-800/30 rounded-2xl border border-dashed border-slate-200 dark:border-white/10">
                            <x-heroicon-o-check-badge class="w-10 h-10 mx-auto text-slate-300 dark:text-zinc-600 mb-2" />
                            <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Tugas Hari Ini Selesai!</p>
                        </div>
                        @endforelse
                    </div>
                </div>

            </div>
            @endif

            @if($activeTab === 'leads')
            <div class="space-y-6 animate-fade-in">
                
                <div class="bg-white dark:bg-zinc-900 p-4 rounded-[2rem] shadow-sm border border-slate-100 dark:border-white/5 sticky top-0 z-20">
                    <div class="flex flex-col md:flex-row gap-4 justify-between">
                        
                        <div class="flex p-1 bg-slate-100 dark:bg-zinc-800 rounded-xl w-full md:w-auto shrink-0">
                            <button wire:click="$set('leadType', 'personal')" 
                                class="flex-1 px-5 py-2.5 rounded-lg text-xs font-black uppercase tracking-wider transition 
                                {{ $leadType === 'personal' ? 'bg-white dark:bg-zinc-700 shadow text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700' }}">
                                Personal
                            </button>
                            <button wire:click="$set('leadType', 'corporate')" 
                                class="flex-1 px-5 py-2.5 rounded-lg text-xs font-black uppercase tracking-wider transition 
                                {{ $leadType === 'corporate' ? 'bg-white dark:bg-zinc-700 shadow text-purple-600 dark:text-white' : 'text-slate-500 hover:text-slate-700' }}">
                                Korporat
                            </button>
                            <button wire:click="$set('leadType', 'converted')" 
                                class="flex-1 px-5 py-2.5 rounded-lg text-xs font-black uppercase tracking-wider transition 
                                {{ $leadType === 'converted' ? 'bg-emerald-600 shadow text-white' : 'text-slate-500 hover:text-slate-700' }}">
                                Closing
                            </button>
                        </div>

                        <div class="flex-1 flex flex-col sm:flex-row gap-3 w-full md:justify-end">
                            @if($leadType === 'converted')
                                <input type="month" wire:model.live="selectedMonth" 
                                    class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold border-2 border-slate-100 dark:border-white/10 rounded-xl bg-slate-50 dark:bg-zinc-800 dark:text-white focus:border-emerald-500 outline-none transition">
                            @else
                                <div class="relative w-full sm:w-64">
                                    <input wire:model.live.debounce.500ms="searchLead" type="text" placeholder="Cari nama / telepon..." 
                                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/10 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:border-indigo-500 outline-none transition placeholder:text-slate-400">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                </div>
                                
                                <div class="flex gap-2 overflow-x-auto pb-1 sm:pb-0 no-scrollbar">
                                    @foreach(['new'=>'Baru', 'contacted'=>'Hubungi', 'warm'=>'Prospek', 'hot'=>'Hot'] as $key => $label)
                                    <button wire:click="$set('filterStatus', '{{ $filterStatus == $key ? '' : $key }}')" 
                                        class="px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider border-2 transition whitespace-nowrap
                                        {{ $filterStatus == $key 
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-500/30' 
                                            : 'bg-white dark:bg-zinc-800 text-slate-500 border-slate-100 dark:border-white/5 hover:border-indigo-200' }}">
                                        {{ $label }}
                                    </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @forelse($this->leads as $lead)
                    <div class="group bg-white dark:bg-zinc-900 p-5 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 relative hover:border-indigo-200 dark:hover:border-indigo-900 transition-all duration-300 hover:shadow-lg flex flex-col h-full">
                        
                        <div class="absolute top-0 right-0 px-5 py-2 rounded-bl-[1.5rem] rounded-tr-[2rem] text-[10px] font-black uppercase tracking-widest shadow-sm
                            {{ match($lead->status) { 
                                'converted' => 'bg-emerald-500 text-white',
                                'hot'       => 'bg-red-500 text-white shadow-red-500/20', 
                                'warm'      => 'bg-amber-400 text-white shadow-amber-500/20',
                                'contacted' => 'bg-blue-500 text-white shadow-blue-500/20',
                                default     => 'bg-slate-200 text-slate-500 dark:bg-white/10 dark:text-zinc-400'
                            } }}">
                            {{ $lead->status == 'converted' ? 'DEAL ✅' : $lead->status }}
                        </div>

                        <div class="flex items-start gap-4 mb-4 mt-2">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl font-black shadow-inner
                                {{ $leadType === 'corporate' 
                                    ? 'bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400' 
                                    : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400' }}">
                                {{ substr($leadType !== 'corporate' ? $lead->name : $lead->company_name, 0, 1) }}
                            </div>
                            
                            <div class="flex-1 min-w-0 pr-16">
                                <h3 class="font-black text-slate-900 dark:text-white text-base truncate leading-tight mb-1" title="{{ $leadType !== 'corporate' ? $lead->name : $lead->company_name }}">
                                    {{ $leadType !== 'corporate' ? $lead->name : $lead->company_name }}
                                </h3>
                                
                                @if($leadType !== 'corporate')
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-400 mb-2">
                                        <x-heroicon-s-map-pin class="w-3 h-3" />
                                        <span class="truncate">{{ $lead->city ?? 'Kota -' }}</span>
                                    </div>
                                    @if($lead->potential_package)
                                        <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-zinc-300 text-[10px] font-bold uppercase tracking-wide truncate max-w-full">
                                            {{ $lead->potential_package }}
                                        </span>
                                    @endif
                                @else
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400">PIC: {{ $lead->pic_name }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Potensi: {{ $lead->potential_pax }} Pax</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($lead->notes)
                        <div class="mb-4 bg-slate-50 dark:bg-zinc-800/50 p-3 rounded-xl border border-slate-100 dark:border-white/5">
                            <p class="text-xs text-slate-500 dark:text-zinc-400 italic line-clamp-2">"{{ $lead->notes }}"</p>
                        </div>
                        @endif

                        <div class="mt-auto grid grid-cols-2 gap-2 pt-4 border-t border-slate-50 dark:border-white/5">
                            @php 
                                $phone = $leadType == 'personal' ? $lead->phone : $lead->pic_phone; 
                                // Format HP 62
                                $waLink = "https://wa.me/" . preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $phone));
                            @endphp
                            
                            <a href="{{ $waLink }}" target="_blank"
                            class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white dark:bg-emerald-900/20 dark:text-emerald-400 dark:hover:bg-emerald-600 dark:hover:text-white font-bold text-xs uppercase tracking-wider transition group/btn">
                                <svg class="w-4 h-4 transition-transform group-hover/btn:scale-110" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                WhatsApp
                            </a>
                            
                            <button wire:click="openEditLead({{ $lead->id }})" 
                                class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-zinc-800 dark:text-zinc-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-bold text-xs uppercase tracking-wider transition">
                                <x-heroicon-s-pencil-square class="w-4 h-4" /> Detail
                            </button>
                        </div>

                        @if(in_array($lead->status, ['hot', 'closing', 'deal']) && $lead->status !== 'converted')
                            <button wire:click="openConvertModal({{ $lead->id }})" class="w-full mt-3 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-500/20 hover:scale-[1.01] transition flex items-center justify-center gap-2">
                                <x-heroicon-s-user-plus class="w-4 h-4" />
                                Convert to Booking
                            </button>
                        @endif
                    </div>
                    @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-200 dark:border-white/5 rounded-[3rem]">
                        <div class="p-6 bg-slate-50 dark:bg-zinc-800 rounded-full mb-4">
                            <x-heroicon-o-funnel class="w-12 h-12 text-slate-300 dark:text-zinc-600" />
                        </div>
                        <h3 class="text-lg font-black text-slate-700 dark:text-white uppercase tracking-tight mb-1">Tidak Ada Data</h3>
                        <p class="text-sm text-slate-400 dark:text-zinc-500">Belum ada prospek dengan status ini.</p>
                    </div>
                    @endforelse
                </div>

                <div class="py-4">
                    {{ $this->leads->links() }}
                </div>
            </div>
            @endif

            @if($activeTab === 'tasks')
            <div class="space-y-6 animate-fade-in">
                
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5">
                    
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-50 dark:border-white/5">
                        <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <x-heroicon-s-clipboard-document-list class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-black text-lg text-slate-800 dark:text-white uppercase tracking-tight">SOP & Tugas Harian</h3>
                            <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">
                                {{ $this->myTasks->count() }} Tugas Tersedia
                            </p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @forelse($this->myTasks as $task)
                        <div class="group bg-slate-50 dark:bg-zinc-800/30 p-5 rounded-2xl border border-slate-200 dark:border-white/5 relative hover:border-emerald-300 dark:hover:border-emerald-800 hover:shadow-lg transition-all duration-300 flex flex-col h-full">
                            
                            <div class="flex justify-between items-start mb-3">
                                <span class="bg-white dark:bg-zinc-700 text-slate-500 dark:text-zinc-300 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border border-slate-100 dark:border-white/5 shadow-sm">
                                    {{ $task->template->frequency ?? 'ADHOC' }}
                                </span>
                                <span class="flex items-center gap-1 text-[10px] font-black text-red-500 bg-red-50 dark:bg-red-900/20 px-2.5 py-1 rounded-lg border border-red-100 dark:border-red-900/30">
                                    <x-heroicon-m-clock class="w-3 h-3" />
                                    {{ $task->due_date->format('d M • H:i') }}
                                </span>
                            </div>
                            
                            <div class="flex-1 mb-4">
                                <h4 class="font-bold text-sm text-slate-800 dark:text-white leading-snug line-clamp-3 group-hover:text-emerald-600 transition-colors">
                                    {{ $task->title }}
                                </h4>
                            </div>
                            
                            <div class="mt-auto pt-4 border-t border-slate-200 dark:border-white/5 border-dashed">
                                @if($task->due_date < now())
                                    <button disabled class="w-full py-2.5 bg-red-50 text-red-500 dark:bg-red-900/10 dark:text-red-400 rounded-xl font-bold text-xs uppercase tracking-wide cursor-not-allowed opacity-75 border border-red-100 dark:border-red-900/30">
                                        Terlewat
                                    </button>
                                @elseif($task->action_url || $task->template?->action_url)
                                    <button wire:click="executeTask({{ $task->id }})" wire:loading.attr="disabled" 
                                        class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-emerald-500/20 transition transform active:scale-95 flex justify-center items-center gap-2 group/btn">
                                        <span wire:loading.remove target="executeTask({{ $task->id }})" class="flex items-center gap-2">
                                            <x-heroicon-s-play class="w-3 h-3 group-hover/btn:animate-pulse" /> Kerjakan
                                        </span>
                                        <span wire:loading target="executeTask({{ $task->id }})" class="flex items-center gap-2">
                                            <x-heroicon-o-arrow-path class="w-3 h-3 animate-spin" /> Proses...
                                        </span>
                                    </button>
                                @else
                                    <button disabled class="w-full py-2.5 bg-slate-200 text-slate-400 dark:bg-zinc-700 dark:text-zinc-500 rounded-xl font-bold text-xs uppercase tracking-wide cursor-not-allowed border border-slate-300 dark:border-zinc-600">
                                        Manual Check
                                    </button>
                                @endif
                            </div>

                        </div>
                        @empty
                        <div class="col-span-full py-16 flex flex-col items-center justify-center text-center border-2 border-dashed border-slate-200 dark:border-white/10 rounded-[2rem]">
                            <div class="w-16 h-16 bg-slate-50 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-3 text-slate-300 dark:text-zinc-600">
                                <x-heroicon-s-check-badge class="w-8 h-8" />
                            </div>
                            <p class="font-bold text-slate-400 dark:text-zinc-500">Tidak ada tugas aktif saat ini.</p>
                            <p class="text-xs text-slate-400 mt-1">Istirahat sejenak atau cari prospek baru!</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

            @if($activeTab === 'profile')
            <div class="flex flex-col items-center justify-center h-full animate-fade-in pb-20 md:pb-0">
                
                <div class="bg-white dark:bg-zinc-900 w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-white/5 relative overflow-hidden group">
                    
                    <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-indigo-600 to-blue-500">
                        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px); background-size: 12px 12px;"></div>
                    </div>
                    
                    <div class="relative z-10 pt-16 px-8 pb-10 text-center">
                        
                        <div class="relative mx-auto w-24 h-24 mb-5">
                            <div class="w-full h-full rounded-[2rem] bg-white dark:bg-zinc-800 p-1.5 shadow-xl rotate-3 group-hover:rotate-0 transition-transform duration-500 ease-out">
                                <div class="w-full h-full rounded-[1.5rem] bg-indigo-50 dark:bg-indigo-900/50 flex items-center justify-center text-3xl font-black text-indigo-600 dark:text-indigo-400 border-2 border-indigo-100 dark:border-indigo-500/20">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            </div>
                        </div>
                        
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                            {{ Auth::user()->name }}
                        </h2>
                        <div class="inline-flex items-center gap-1.5 mt-2 px-3 py-1 bg-slate-100 dark:bg-zinc-800 rounded-full">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                            <p class="text-[10px] font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-widest">
                                Sales Executive
                            </p>
                        </div>
                        
                        <div class="mt-8 space-y-4">
                            <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                                @csrf
                                <button type="submit" class="w-full py-4 bg-red-50 text-red-600 hover:bg-red-500 hover:text-white dark:bg-red-900/10 dark:text-red-400 dark:hover:bg-red-600 dark:hover:text-white rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-sm hover:shadow-lg hover:shadow-red-500/20 flex items-center justify-center gap-3 group/btn">
                                    <x-heroicon-s-arrow-left-on-rectangle class="w-5 h-5 group-hover/btn:-translate-x-1 transition-transform" />
                                    Keluar Aplikasi
                                </button>
                            </form>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-50 dark:border-white/5">
                            <p class="text-[10px] text-slate-300 dark:text-zinc-600 font-bold uppercase tracking-widest">
                                Rawabi Sales System v2.0
                            </p>
                        </div>

                    </div>
                </div>
            </div>
            @endif

        </main>

        <nav class="md:hidden fixed bottom-6 left-4 right-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-lg border border-slate-200 dark:border-white/5 flex justify-around items-center py-3 z-40 rounded-3xl shadow-[0_10px_30px_-10px_rgba(0,0,0,0.2)]"
            style="
                background-image: url('/images/ornaments/arabesque.png');
                background-repeat: repeat;
                background-size: 150px 150px;
            ">
            
            <button wire:click="setTab('home')" class="relative flex flex-col items-center gap-1 w-16 transition-all duration-300 {{ $activeTab === 'home' ? 'text-indigo-600 dark:text-indigo-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                @if($activeTab === 'home') <div class="absolute -top-3 w-1 h-1 bg-indigo-600 rounded-full shadow-[0_0_8px_#4f46e5]"></div> @endif
                <x-heroicon-s-home class="w-6 h-6" />
                <span class="text-[9px] font-black uppercase tracking-tighter">Home</span>
            </button>

            <button wire:click="setTab('leads')" class="relative flex flex-col items-center gap-1 w-16 transition-all duration-300 {{ $activeTab === 'leads' ? 'text-blue-600 dark:text-blue-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                @if($activeTab === 'leads') <div class="absolute -top-3 w-1 h-1 bg-blue-600 rounded-full shadow-[0_0_8px_#2563eb]"></div> @endif
                <x-heroicon-s-user-group class="w-6 h-6" />
                <span class="text-[9px] font-black uppercase tracking-tighter">Leads</span>
            </button>

            <button wire:click="resetForm" @click="showLeadModal = true" class="relative -top-6 bg-indigo-600 text-white w-14 h-14 rounded-full shadow-xl shadow-indigo-600/40 border-4 border-gray-50 dark:border-zinc-950 flex items-center justify-center hover:scale-105 active:scale-95 transition">
                <x-heroicon-s-plus class="w-8 h-8" />
            </button>

            <button wire:click="setTab('tasks')" class="relative flex flex-col items-center gap-1 w-16 transition-all duration-300 {{ $activeTab === 'tasks' ? 'text-emerald-600 dark:text-emerald-400 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                @if($activeTab === 'tasks') <div class="absolute -top-3 w-1 h-1 bg-emerald-600 rounded-full shadow-[0_0_8px_#059669]"></div> @endif
                <x-heroicon-s-clipboard-document-check class="w-6 h-6" />
                <span class="text-[9px] font-black uppercase tracking-tighter">Tasks</span>
            </button>

            <button wire:click="setTab('profile')" class="relative flex flex-col items-center gap-1 w-16 transition-all duration-300 {{ $activeTab === 'profile' ? 'text-slate-800 dark:text-white scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                @if($activeTab === 'profile') <div class="absolute -top-3 w-1 h-1 bg-slate-800 dark:bg-white rounded-full shadow-[0_0_8px_#1e293b]"></div> @endif
                <x-heroicon-s-user-circle class="w-6 h-6" />
                <span class="text-[9px] font-black uppercase tracking-tighter">Akun</span>
            </button>

        </nav>

        <div x-show="showLeadModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
    
            <div @click="showLeadModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

            <div class="relative bg-white dark:bg-zinc-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl flex flex-col max-h-[90vh] border border-white/10 overflow-hidden" x-transition.move.up>
                
                <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                            @if($leadType === 'corporate')
                                <div class="p-2 bg-purple-50 dark:bg-purple-500/10 rounded-xl text-purple-600 dark:text-purple-400">
                                    <x-heroicon-s-building-office class="w-6 h-6" />
                                </div>
                            @else
                                <div class="p-2 bg-blue-50 dark:bg-blue-500/10 rounded-xl text-blue-600 dark:text-blue-400">
                                    <x-heroicon-s-user class="w-6 h-6" />
                                </div>
                            @endif
                            {{ $isEditing ? 'Edit Lead' : 'Lead Baru' }}
                        </h3>
                        <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-1 ml-14 uppercase tracking-widest">
                            {{ ucfirst($leadType) }} Prospect
                        </p>
                    </div>
                    <button @click="showLeadModal = false" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                        <x-heroicon-s-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
                    
                    @if($leadType === 'personal')
                        <div class="space-y-4">
                            
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Nama Calon Jamaah</label>
                                <input wire:model="name" type="text" placeholder="Masukkan nama lengkap..." 
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all placeholder:text-slate-300">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">WhatsApp</label>
                                    <div class="flex gap-2">
                                        <div class="w-14 flex items-center justify-center bg-slate-100 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-slate-500 font-black text-sm">+62</div>
                                        <input wire:model="phone" type="number" placeholder="812xxxx" 
                                            class="flex-1 px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all placeholder:text-slate-300">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Domisili</label>
                                    <input wire:model="city" type="text" placeholder="Kota tinggal..." 
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all placeholder:text-slate-300">
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Sumber Info</label>
                                <div class="relative">
                                    <select wire:model.live="source" class="w-full pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all appearance-none cursor-pointer">
                                        <option value="">-- Pilih Sumber --</option>
                                        <option value="Facebook Ads">Facebook Ads</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="Tiktok">Tiktok</option>
                                        <option value="Website">Website</option>
                                        <option value="Agent">Referensi Agen</option>
                                        <option value="Walk-in">Datang ke Kantor</option>
                                        <option value="Referral">Referral Teman</option>
                                    </select>
                                    <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t-2 border-dashed border-slate-100 dark:border-white/5 my-2"></div>

                        <div class="space-y-4">
                            <label class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-indigo-500 rounded-full"></span>
                                Detail Prospek
                            </label>

                            @if($source === 'Agent')
                            <div class="animate-fade-in bg-orange-50 dark:bg-orange-900/10 p-4 rounded-xl border border-orange-100 dark:border-orange-800/30">
                                <label class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest mb-2 block">Agen Referensi</label>
                                <div class="relative">
                                    <select wire:model="agent_id" class="w-full pl-4 pr-10 py-2.5 bg-white dark:bg-zinc-900 border-2 border-orange-200 dark:border-orange-800 rounded-xl text-sm font-bold text-slate-700 dark:text-white focus:border-orange-500 outline-none appearance-none">
                                        <option value="">-- Pilih Agen --</option>
                                        @foreach($this->agents as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <x-heroicon-s-users class="w-4 h-4 text-orange-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                            </div>
                            @endif

                            <div class="bg-slate-50 dark:bg-zinc-800/50 p-3 rounded-xl border border-slate-100 dark:border-white/5 flex justify-between items-center opacity-70">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sales Handle</p>
                                    <p class="text-sm font-black text-slate-700 dark:text-zinc-300">{{ auth()->user()->name }}</p>
                                </div>
                                <x-heroicon-s-lock-closed class="w-4 h-4 text-slate-300" />
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Minat Paket</label>
                                <div class="relative">
                                    <select wire:model="potential_package" class="w-full pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all appearance-none cursor-pointer">
                                        <option value="">-- Pilih Paket --</option>
                                        @foreach($this->packages as $packageName => $label)
                                            <option value="{{ $packageName }}">{{ $label }}</option>
                                        @endforeach
                                        <option value="General">Belum Tahu (General)</option>
                                    </select>
                                    <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block ml-1">Status Prospek</label>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach([
                                        'cold' => ['label' => '❄️ Cold', 'style' => 'bg-blue-100 text-blue-700 border-blue-200 ring-blue-500'],
                                        'warm' => ['label' => '🔥 Warm', 'style' => 'bg-amber-100 text-amber-700 border-amber-200 ring-amber-500'],
                                        'hot' => ['label' => '⚡ Hot', 'style' => 'bg-red-100 text-red-700 border-red-200 ring-red-500'],
                                        'closing' => ['label' => '✅ Deal', 'style' => 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-500']
                                    ] as $val => $conf)
                                    <button type="button" wire:click="$set('status', '{{ $val }}')" 
                                        class="py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wide border-2 transition-all transform active:scale-95
                                        {{ $status === $val ? $conf['style'] . ' ring-2 ring-offset-2 dark:ring-offset-zinc-900 scale-105' : 'bg-slate-50 dark:bg-zinc-800 border-slate-100 dark:border-white/5 text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10' }}">
                                        {{ $conf['label'] }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Catatan Follow Up</label>
                                <textarea wire:model="notes" rows="3" placeholder="Hasil ngobrol, janji temu, dll..." class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-medium text-slate-700 dark:text-zinc-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all resize-none"></textarea>
                            </div>
                        </div>

                    @else
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Nama Instansi / Perusahaan</label>
                                <input wire:model="company_name" type="text" placeholder="PT. Travel Sejahtera..." 
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Nama PIC</label>
                                    <input wire:model="pic_name" type="text" placeholder="Bpk. Budi..." 
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold focus:border-purple-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Kontak PIC</label>
                                    <input wire:model="pic_phone" type="number" placeholder="0812..." 
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold focus:border-purple-500 outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Alamat Kantor</label>
                                <input wire:model="address" type="text" placeholder="Jl. Sudirman No..." 
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold focus:border-purple-500 outline-none">
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Potensi Pax</label>
                                <input wire:model="potential_pax" type="number" placeholder="Estimasi jumlah jamaah..." 
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold focus:border-purple-500 outline-none">
                            </div>

                            <div class="bg-slate-50 dark:bg-zinc-800/50 p-3 rounded-xl border border-slate-100 dark:border-white/5 flex justify-between items-center opacity-70">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sales Handle</p>
                                    <p class="text-sm font-black text-slate-700 dark:text-zinc-300">{{ auth()->user()->name }}</p>
                                </div>
                                <x-heroicon-s-lock-closed class="w-4 h-4 text-slate-300" />
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Catatan</label>
                                <textarea wire:model="notes" rows="3" placeholder="Kebutuhan khusus, budget, dll..." class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-medium focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none resize-none"></textarea>
                            </div>
                        </div>
                    @endif

                </div>

                <div class="p-5 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-zinc-900/80 flex gap-3 shrink-0">
                    <button @click="showLeadModal = false" wire:click="resetForm" 
                        class="flex-1 py-3 bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-300 dark:hover:bg-zinc-700 transition">
                        Batal
                    </button>
                    <button wire:click="saveLead" wire:loading.attr="disabled" 
                        class="flex-[2] py-3 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition transform active:scale-95 flex justify-center items-center gap-2">
                        <span wire:loading.remove>{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Lead Baru' }}</span>
                        <span wire:loading>Memproses...</span>
                    </button>
                </div>

            </div>
        </div>

        <div x-show="showReportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
    
            <div @click="showReportModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

            <div class="relative bg-white dark:bg-zinc-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl flex flex-col max-h-[90vh] border border-white/10 overflow-hidden" x-transition.move.up>
                
                <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                            <div class="p-2 bg-orange-50 dark:bg-orange-500/10 rounded-xl text-orange-600 dark:text-orange-400">
                                <x-heroicon-s-pencil-square class="w-6 h-6" />
                            </div>
                            Lapor Kegiatan
                        </h3>
                        <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-1 ml-14 uppercase tracking-widest">
                            Update Aktivitas Harian
                        </p>
                    </div>
                    <button @click="showReportModal = false" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                        <x-heroicon-s-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5">
                    
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Jenis Aktivitas</label>
                        <div class="relative">
                            <select wire:model="report_type" class="w-full pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-orange-500/50 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all appearance-none cursor-pointer">
                                <option value="canvasing">📢 Canvasing / Flyering</option>
                                <option value="meeting">🤝 Meeting Luar Kantor</option>
                                <option value="event">🎉 Jaga Booth / Event</option>
                                <option value="follow_up">📞 Follow Up Umum</option>
                            </select>
                            <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Lokasi</label>
                            <div class="relative">
                                <input wire:model="report_loc" type="text" placeholder="Ct: Masjid Al-Azhar" 
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all placeholder:text-slate-300">
                                <x-heroicon-s-map-pin class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Estimasi Prospek</label>
                            <div class="relative">
                                <input wire:model="report_qty" type="number" placeholder="0" 
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all placeholder:text-slate-300">
                                <x-heroicon-s-users class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Laporan Hasil</label>
                        <textarea wire:model="report_desc" rows="4" placeholder="Ceritakan hasil kegiatan, kendala, atau feedback lapangan..." 
                            class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-medium text-slate-700 dark:text-zinc-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all resize-none placeholder:text-slate-300"></textarea>
                    </div>

                </div>

                <div class="p-5 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-zinc-900/80 flex gap-3 shrink-0">
                    <button @click="showReportModal = false" class="flex-1 py-3 bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-300 dark:hover:bg-zinc-700 transition">
                        Batal
                    </button>
                    <button wire:click="saveActivityReport" class="flex-[2] py-3 bg-orange-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-orange-700 shadow-lg shadow-orange-500/30 transition transform active:scale-95 flex justify-center items-center gap-2">
                        <x-heroicon-s-paper-airplane class="w-4 h-4" />
                        Kirim Laporan
                    </button>
                </div>

            </div>
        </div>

        <div x-data="{ showConvertModal: false }" 
            x-on:open-convert-modal.window="showConvertModal = true; $wire.set('convertStep', 1)"
            x-show="showConvertModal" 
            class="fixed inset-0 z-[100] flex items-center justify-center p-4" 
            style="display: none;" 
            x-transition.opacity>
            
            <div @click="showConvertModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

            <div class="relative bg-white dark:bg-zinc-900 w-full max-w-xl rounded-[2.5rem] shadow-2xl flex flex-col max-h-[90vh] border border-white/10 overflow-hidden" x-transition.move.up>
                
                <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                            <span class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-blue-500/30">
                                {{ $convertStep }}
                            </span>
                            <span>
                                @if($convertStep == 1) Mode Pendaftaran
                                @elseif($convertStep == 2) Data Jamaah
                                @else Booking & Pembayaran
                                @endif
                            </span>
                        </h3>
                        <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-1 ml-11 uppercase tracking-widest">
                            Proses Konversi Lead ke Booking
                        </p>
                    </div>
                    <button @click="showConvertModal = false" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                        <x-heroicon-s-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1">

                    @if($convertStep === 1)
                    <div class="space-y-6 animate-fade-in">
                        <div class="text-center">
                            <h4 class="text-sm font-bold text-slate-800 dark:text-white">Tentukan Jenis Pendaftaran</h4>
                            <p class="text-xs text-slate-500 mt-1">Apakah ini jamaah baru atau sudah pernah mendaftar sebelumnya?</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <button wire:click="$set('convertMode', 'new')" 
                                class="p-6 rounded-2xl border-2 transition-all flex flex-col items-center gap-3 group hover:scale-[1.02]
                                {{ $convertMode === 'new' 
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 ring-4 ring-blue-500/10' 
                                    : 'border-slate-100 dark:border-zinc-800 bg-white dark:bg-zinc-800 text-slate-500 hover:border-blue-300' 
                                }}">
                                <div class="p-3 rounded-full {{ $convertMode === 'new' ? 'bg-blue-200 dark:bg-blue-800' : 'bg-slate-100 dark:bg-zinc-700' }}">
                                    <x-heroicon-s-user-plus class="w-8 h-8" />
                                </div>
                                <span class="font-black text-sm uppercase tracking-wide">Daftar Baru</span>
                            </button>
                            
                            <button wire:click="$set('convertMode', 'existing')" 
                                class="p-6 rounded-2xl border-2 transition-all flex flex-col items-center gap-3 group hover:scale-[1.02]
                                {{ $convertMode === 'existing' 
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 ring-4 ring-blue-500/10' 
                                    : 'border-slate-100 dark:border-zinc-800 bg-white dark:bg-zinc-800 text-slate-500 hover:border-blue-300' 
                                }}">
                                <div class="p-3 rounded-full {{ $convertMode === 'existing' ? 'bg-blue-200 dark:bg-blue-800' : 'bg-slate-100 dark:bg-zinc-700' }}">
                                    <x-heroicon-s-magnifying-glass class="w-8 h-8" />
                                </div>
                                <span class="font-black text-sm uppercase tracking-wide">Sudah Ada (RO)</span>
                            </button>
                        </div>

                        @if($convertMode === 'existing')
                        <div class="bg-slate-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-slate-100 dark:border-white/5 animate-fade-in">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Cari Database Jamaah</label>
                            <div class="relative">
                                <select wire:model="existing_jamaah_id" class="w-full pl-4 pr-10 py-3 bg-white dark:bg-zinc-900 border-2 border-slate-200 dark:border-zinc-700 rounded-xl text-sm font-bold text-slate-700 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Pilih Jamaah --</option>
                                    @foreach(Jamaah::limit(20)->get() as $j)
                                        <option value="{{ $j->id }}">{{ $j->name }} - {{ $j->nik }}</option>
                                    @endforeach
                                </select>
                                <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    @if($convertStep === 2)
                    <div class="space-y-6 animate-fade-in">
                        @if($convertMode === 'new')
                            <div class="bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-800/30">
                                <h4 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                    <x-heroicon-s-key class="w-4 h-4" /> Akun Aplikasi Jamaah
                                </h4>
                                <div class="space-y-3">
                                    <input wire:model="new_email" type="email" placeholder="Email (untuk Login) *" class="w-full px-4 py-2.5 bg-white dark:bg-zinc-900 border border-blue-200 dark:border-blue-800/50 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white placeholder-slate-400">
                                    <input wire:model="new_password" type="password" placeholder="Password *" class="w-full px-4 py-2.5 bg-white dark:bg-zinc-900 border border-blue-200 dark:border-blue-800/50 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white placeholder-slate-400">
                                </div>
                            </div>

                            <div class="space-y-4">
                                <input wire:model="new_nik" type="number" placeholder="NIK KTP (16 Digit) *" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 outline-none transition-all placeholder:text-slate-400">
                                <input wire:model="new_name" type="text" placeholder="Nama Lengkap *" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 outline-none transition-all placeholder:text-slate-400">
                                
                                <div class="flex gap-3">
                                    <div class="w-1/2 relative">
                                        <select wire:model="new_gender" class="w-full pl-4 pr-8 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 outline-none appearance-none cursor-pointer">
                                            <option value="">Gender *</option>
                                            <option value="pria">Pria</option>
                                            <option value="wanita">Wanita</option>
                                        </select>
                                        <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>
                                    <div class="flex-1 flex gap-2">
                                        <div class="w-14 flex items-center justify-center bg-slate-100 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-slate-500 font-black text-sm">+62</div>
                                        <input wire:model="new_phone" type="number" placeholder="No WA *" class="flex-1 px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 outline-none placeholder:text-slate-400">
                                    </div>
                                </div>

                                <textarea wire:model="new_address" rows="2" placeholder="Alamat Domisili Lengkap" class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:border-blue-500 outline-none resize-none placeholder:text-slate-400"></textarea>
                                
                                <div x-data="{ openDoc: false }" class="pt-2">
                                    <button @click="openDoc = !openDoc" class="w-full flex justify-between items-center px-4 py-2 bg-slate-100 dark:bg-zinc-800 rounded-lg text-xs font-bold text-slate-500 hover:text-blue-600 transition">
                                        <span x-text="openDoc ? 'Sembunyikan Detail Dokumen' : 'Isi Detail Dokumen (Opsional)'"></span>
                                        <x-heroicon-m-chevron-down class="w-4 h-4 transition-transform" x-bind:class="openDoc ? 'rotate-180' : ''" />
                                    </button>
                                    
                                    <div x-show="openDoc" class="mt-4 space-y-4 pt-2 border-t border-dashed border-slate-200 dark:border-white/10 animate-fade-in" style="display: none;">
                                        <input wire:model="new_passport_number" type="text" placeholder="Nomor Paspor" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 outline-none">
                                        <div class="flex gap-4">
                                            <div class="w-1/2">
                                                <label class="text-[10px] text-slate-400 mb-1 block uppercase font-bold ml-1">Masa Berlaku Paspor</label>
                                                <input wire:model="new_passport_expiry" type="date" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:border-blue-500 outline-none">
                                            </div>
                                            <div class="w-1/2">
                                                <label class="text-[10px] text-slate-400 mb-1 block uppercase font-bold ml-1">Ukuran Baju</label>
                                                <input wire:model="new_shirt_size" type="text" placeholder="S/M/L/XL" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-center uppercase focus:border-blue-500 outline-none">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-6 bg-green-50 dark:bg-green-900/10 text-center rounded-[2rem] border border-green-100 dark:border-green-800/30">
                                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-3 text-green-600">
                                    <x-heroicon-s-check-badge class="w-8 h-8" />
                                </div>
                                <h4 class="font-black text-lg text-green-800 dark:text-green-400 mb-1">Data Jamaah Terpilih</h4>
                                <p class="text-sm text-green-600 dark:text-green-500/80">Silakan lanjut ke tahap booking & pembayaran.</p>
                            </div>
                        @endif
                    </div>
                    @endif

                    @if($convertStep === 3)
                    <div class="space-y-6 animate-fade-in pb-2">
                        
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Pilih Paket Umrah</label>
                            <div class="relative">
                                <select wire:model.live="booking_package_id" class="w-full pl-4 pr-10 py-3.5 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800/50 rounded-xl text-sm font-bold text-blue-900 dark:text-blue-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none appearance-none cursor-pointer">
                                    <option value="">-- Pilih Paket --</option>
                                    @foreach($this->packagesList as $pkg)
                                        <option value="{{ $pkg->id }}">{{ $pkg->name }} ({{ Carbon::parse($pkg->departure_date)->format('d M') }})</option>
                                    @endforeach
                                </select>
                                <x-heroicon-s-chevron-down class="w-5 h-5 text-blue-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>

                        <div class="bg-slate-50 dark:bg-zinc-800 p-5 rounded-2xl flex justify-between items-center border border-slate-200 dark:border-white/5 shadow-inner">
                            <span class="text-sm font-bold text-slate-500 dark:text-zinc-400">Total Harga Paket</span>
                            <span class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Rp {{ number_format($booking_price, 0, ',', '.') }}</span>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-white/5 pb-2">
                                <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                                <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Pembayaran DP</h3>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Nominal DP (Rp)</label>
                                <input wire:model="dp_amount" type="number" class="w-full text-3xl font-black text-right p-4 bg-white dark:bg-zinc-900 border-2 border-emerald-500 rounded-2xl text-emerald-600 dark:text-emerald-400 focus:ring-4 focus:ring-emerald-500/10 outline-none placeholder:text-slate-200" placeholder="0">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="cursor-pointer group relative">
                                    <input type="radio" wire:model.live="dp_method" value="cash" class="peer sr-only">
                                    <div class="p-4 border-2 rounded-2xl text-center font-bold text-slate-400 dark:text-zinc-500 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 transition-all hover:bg-slate-50 dark:hover:bg-zinc-800 flex flex-col items-center gap-2 h-full justify-center">
                                        <x-heroicon-s-banknotes class="w-8 h-8" />
                                        <span class="text-xs uppercase tracking-wider">Tunai (Cash)</span>
                                    </div>
                                    <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <x-heroicon-s-check-circle class="w-5 h-5 text-emerald-500" />
                                    </div>
                                </label>

                                <label class="cursor-pointer group relative">
                                    <input type="radio" wire:model.live="dp_method" value="transfer" class="peer sr-only">
                                    <div class="p-4 border-2 rounded-2xl text-center font-bold text-slate-400 dark:text-zinc-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 peer-checked:text-blue-700 dark:peer-checked:text-blue-400 transition-all hover:bg-slate-50 dark:hover:bg-zinc-800 flex flex-col items-center gap-2 h-full justify-center">
                                        <x-heroicon-s-building-library class="w-8 h-8" />
                                        <span class="text-xs uppercase tracking-wider">Transfer Bank</span>
                                    </div>
                                    <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <x-heroicon-s-check-circle class="w-5 h-5 text-blue-500" />
                                    </div>
                                </label>
                            </div>

                            @if($dp_method)
                            <div class="animate-fade-in p-4 bg-slate-50 dark:bg-zinc-800/50 rounded-2xl border border-slate-100 dark:border-white/5 space-y-3">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">
                                        {{ $dp_method == 'cash' ? 'Masuk ke Kasir:' : 'Transfer ke Rekening:' }}
                                    </label>
                                    <div class="relative">
                                        <select wire:model="target_wallet_id" class="w-full pl-4 pr-10 py-3 bg-white dark:bg-zinc-900 border-2 border-slate-200 dark:border-zinc-700 rounded-xl text-sm font-bold text-slate-700 dark:text-white focus:border-slate-400 outline-none appearance-none">
                                            <option value="">-- Pilih Tujuan --</option>
                                            @foreach($this->wallets as $wallet)
                                                @if($dp_method == 'cash' && $wallet->type == 'cashier')
                                                    <option value="{{ $wallet->id }}">{{ $wallet->name }}</option>
                                                @elseif($dp_method == 'transfer' && $wallet->type == 'bank')
                                                    <option value="{{ $wallet->id }}">{{ $wallet->name }} ({{ $wallet->account_number }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Upload Bukti Transaksi</label>
                                    <div class="relative group cursor-pointer">
                                        <div class="border-2 border-dashed border-slate-300 dark:border-zinc-600 rounded-xl p-4 text-center bg-white dark:bg-zinc-900 group-hover:border-emerald-500 transition-colors">
                                            <input wire:model="dp_proof" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                            <div class="flex items-center justify-center gap-3 pointer-events-none">
                                                <x-heroicon-o-arrow-up-tray class="w-6 h-6 text-slate-400 group-hover:text-emerald-500" />
                                                <span class="text-sm font-bold text-slate-500 group-hover:text-emerald-600">Klik untuk upload bukti</span>
                                            </div>
                                        </div>
                                        @if($dp_proof)
                                            <p class="text-xs text-emerald-600 font-bold mt-2 text-center">File terpilih: {{ $dp_proof->getClientOriginalName() }}</p>
                                        @endif
                                        @error('dp_proof') <span class="text-xs text-red-500 font-bold block mt-1 text-center">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                </div>

                <div class="p-6 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-zinc-900/80 flex gap-4 shrink-0">
                    @if($convertStep > 1)
                        <button wire:click="$set('convertStep', {{ $convertStep - 1 }})" class="flex-1 py-3.5 bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-300 dark:hover:bg-zinc-700 transition">
                            Kembali
                        </button>
                    @endif

                    @if($convertStep < 3)
                        <button wire:click="goToStep{{ $convertStep + 1 }}" class="flex-[2] py-3.5 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition transform active:scale-95 flex justify-center items-center gap-2">
                            Lanjut <x-heroicon-s-arrow-right class="w-4 h-4" />
                        </button>
                    @else
                        <button wire:click="processFinalConversion" wire:loading.attr="disabled" class="flex-[2] py-3.5 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-700 shadow-lg shadow-emerald-500/30 transition transform active:scale-95 flex justify-center items-center gap-2">
                            <span wire:loading.remove>Booking Sekarang</span>
                            <span wire:loading class="flex items-center gap-2">
                                <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" /> Memproses...
                            </span>
                        </button>
                    @endif
                </div>

            </div>
        </div>

        <div x-show="showMediaModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
    
            <div @click="showMediaModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

            <div class="relative bg-white dark:bg-zinc-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl flex flex-col max-h-[90vh] border border-white/10 overflow-hidden" x-transition.move.up>
                
                <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                            <div class="p-2 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl text-indigo-600 dark:text-indigo-400">
                                <x-heroicon-s-swatch class="w-6 h-6" />
                            </div>
                            Creative Support
                        </h3>
                        <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-1 ml-14 uppercase tracking-widest">
                            Marketing Tools & Assets
                        </p>
                    </div>
                    <button @click="showMediaModal = false" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                        <x-heroicon-s-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-2 mx-6 mt-4 bg-slate-100 dark:bg-zinc-800 rounded-2xl flex shrink-0">
                    <button wire:click="$set('mediaTab', 'upload')" 
                        class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition flex justify-center items-center gap-2 
                        {{ $mediaTab === 'upload' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                        <x-heroicon-s-arrow-up-tray class="w-4 h-4" /> Upload Aset
                    </button>
                    <button wire:click="$set('mediaTab', 'request')" 
                        class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition flex justify-center items-center gap-2 
                        {{ $mediaTab === 'request' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                        <x-heroicon-s-pencil-square class="w-4 h-4" /> Request Desain
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
                    
                    @if($mediaTab === 'upload')
                    <div class="space-y-5 animate-fade-in">
                        
                        <div class="bg-indigo-50 dark:bg-indigo-900/10 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800/30">
                            <label class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-2 block flex items-center gap-1">
                                <x-heroicon-s-folder class="w-3 h-3" />
                                Simpan Ke Folder
                            </label>
                            <div class="relative">
                                <select wire:model.live="selectedPackageId" class="w-full pl-4 pr-10 py-2.5 bg-white dark:bg-zinc-900 border-2 border-indigo-200 dark:border-indigo-800 rounded-xl text-sm font-bold text-slate-700 dark:text-white focus:border-indigo-500 outline-none appearance-none cursor-pointer">
                                    <option value="">-- Folder Umum / Non-Grup --</option>
                                    @foreach($this->mediaPackages as $pkg) 
                                        <option value="{{ $pkg->id }}">
                                            {{ Str::limit($pkg->name, 40) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-heroicon-s-chevron-down class="w-4 h-4 text-indigo-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>

                        <div class="relative group cursor-pointer">
                            <div class="border-2 border-dashed border-slate-300 dark:border-zinc-700 rounded-2xl p-8 text-center bg-slate-50 dark:bg-zinc-800/50 hover:border-indigo-500 dark:hover:border-indigo-500 transition-colors">
                                <input type="file" wire:model="mediaPhotos" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                
                                <div class="space-y-3 pointer-events-none">
                                    <div wire:loading wire:target="mediaPhotos">
                                        <x-heroicon-o-arrow-path class="w-12 h-12 mx-auto text-indigo-500 animate-spin" />
                                        <p class="text-xs text-indigo-600 font-bold uppercase tracking-widest mt-2">Uploading...</p>
                                    </div>
                                    <div wire:loading.remove wire:target="mediaPhotos">
                                        <div class="w-14 h-14 bg-white dark:bg-zinc-700 shadow-sm rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition text-indigo-500">
                                            <x-heroicon-s-camera class="w-7 h-7" />
                                        </div>
                                        <p class="text-sm font-bold text-slate-700 dark:text-zinc-200 group-hover:text-indigo-600 transition">Ambil Foto / Pilih File</p>
                                        <p class="text-[10px] text-slate-400 font-medium mt-1">Bisa upload banyak sekaligus</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Label / Keterangan</label>
                            <div class="relative">
                                <input wire:model="mediaTags" type="text" placeholder="Contoh: Bukti Transfer, Invoice..." 
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-400">
                                <x-heroicon-s-tag class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            </div>
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
                    <div class="space-y-5 animate-fade-in">
                        
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Judul Request</label>
                            <input wire:model="reqTitle" type="text" placeholder="Contoh: Desain Flyer Promo" 
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-400">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Detail Kebutuhan</label>
                            <textarea wire:model="reqDesc" rows="4" placeholder="Jelaskan warna, teks, ukuran, referensi..." 
                                class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-medium text-slate-700 dark:text-zinc-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all resize-none placeholder:text-slate-400"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Deadline</label>
                                <div class="relative">
                                    <input wire:model="reqDeadline" type="date" 
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all cursor-pointer">
                                    <x-heroicon-s-calendar class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block ml-1">Prioritas</label>
                                <div class="relative">
                                    <select wire:model="reqPriority" class="w-full pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-bold text-slate-700 dark:text-zinc-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none appearance-none cursor-pointer">
                                        <option value="low">☕ Santai (Low)</option>
                                        <option value="medium">📝 Standar (Medium)</option>
                                        <option value="high">🔥 Urgent (High)</option>
                                    </select>
                                    <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
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
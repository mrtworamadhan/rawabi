<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Department;
use App\Models\Expense;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Lead;
use App\Models\CorporateLead;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\OfficeWallet;
use App\Models\ContentRequest;
use App\Models\ContentSchedule;
use App\Models\MediaAsset;
use App\Models\RoomAssignment;
use App\Models\Task;
use App\Models\UmrahPackage;
use App\Models\PackageFlight;
use App\Models\PackageHotel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Barryvdh\DomPDF\Facade\Pdf;


new #[Layout('layouts::app')] class extends Component {
    public $activeTab = 'analytics';

    public $analyticsPeriod = 'monthly';

    public $selectedBatchId = null;

    public $dateStart;
    public $dateEnd;

    public $showLeadsModal = false;
    public $leadsDetailType = 'personal';
    public $leadsData = [];

    public $showMediaListModal = false;
    public $mediaListType = 'published';
    public $mediaListData = [];

    public $showHrModal = false;
    public $selectedEmployeeName = '';
    public $employeeTasks = [];

    public $analyticsYear;

    public function mount()
    {
        $this->dateEnd = now()->format('Y-m-d');
        $this->dateStart = now()->subDays(6)->format('Y-m-d');
        $this->analyticsYear = now()->year;
    }

    public function getAnalyticsStatsProperty()
    {
        $year = $this->analyticsYear;
        $lastYear = $year - 1;
        $months = range(1, 12);

        // Helper: Hitung Growth %
        $calcGrowth = function ($current, $previous) {
            if ($previous == 0)
                return $current > 0 ? 100 : 0;
            return round((($current - $previous) / $previous) * 100, 1);
        };

        // --- 1. FINANCIAL (Income, Expense, HPP, Operational) ---
        $incRaw = Payment::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->whereYear('created_at', $year)->whereNotNull('verified_at')
            ->groupBy('month')->pluck('total', 'month')->toArray();

        // Expense Total (Approved)
        $expRaw = Expense::selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->whereYear('transaction_date', $year)->where('status', 'approved')
            ->groupBy('month')->pluck('total', 'month')->toArray();

        // Expense Breakdown: HPP
        $hppRaw = Expense::selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->whereYear('transaction_date', $year)->where('status', 'approved')
            ->whereHas('category', fn($q) => $q->where('type', 'hpp'))
            ->groupBy('month')->pluck('total', 'month')->toArray();

        // Expense Breakdown: Operational
        $opsRaw = Expense::selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->whereYear('transaction_date', $year)->where('status', 'approved')
            ->whereHas('category', fn($q) => $q->where('type', 'operational'))
            ->groupBy('month')->pluck('total', 'month')->toArray();

        // Data Tahun Lalu (Untuk Growth)
        $incLastYear = Payment::whereYear('created_at', $lastYear)->whereNotNull('verified_at')->sum('amount');

        // Sum Totals
        $incTotal = array_sum($incRaw);
        $expTotal = array_sum($expRaw);
        $hppTotal = array_sum($hppRaw);
        $opsTotal = array_sum($opsRaw);


        // --- 2. JAMAAH & LEADS ---
        $jamaahRaw = Booking::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)->where('status', '!=', 'cancelled')
            ->groupBy('month')->pluck('total', 'month')->toArray();

        $leadsRaw = Lead::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')->pluck('total', 'month')->toArray();

        // Growth Data
        $jamaahLastYear = Booking::whereYear('created_at', $lastYear)->where('status', '!=', 'cancelled')->count();
        $jamaahTotal = array_sum($jamaahRaw);

        // Fix Conversion Rate Logic
        $leadsTotalInput = array_sum($leadsRaw);
        $effectiveLeads = max($leadsTotalInput, $jamaahTotal);
        $conversionRate = $effectiveLeads > 0 ? ($jamaahTotal / $effectiveLeads) * 100 : 0;


        // --- 3. CONTENT PERFORMANCE ---
        $allSchedules = ContentSchedule::whereYear('scheduled_date', $year)
            ->where('status', 'published')->pluck('platforms');

        $platformCounts = ['instagram' => 0, 'tiktok' => 0, 'facebook' => 0, 'youtube' => 0];

        foreach ($allSchedules as $platformsArray) {
            if (is_string($platformsArray))
                $platformsArray = json_decode($platformsArray, true);
            if (is_array($platformsArray)) {
                foreach ($platformsArray as $p) {
                    $p = strtolower($p);
                    if (isset($platformCounts[$p]))
                        $platformCounts[$p]++;
                }
            }
        }

        // --- 4. HR PRODUCTIVITY ---
        $depts = Department::whereNotIn('name', ['BOD', 'Board of Directors', 'Direksi'])
            ->whereNotIn('code', ['BOD', 'DIR'])->take(5)->get();

        $hrLabels = [];
        $hrData = [];

        foreach ($depts as $dept) {
            $qBase = Task::whereHas('employee', fn($q) => $q->where('department_id', $dept->id))
                ->whereYear('created_at', $year);

            $totalTask = (clone $qBase)->count();
            $doneTask = (clone $qBase)->where('status', 'completed')->count();

            $hrLabels[] = $dept->name;
            $hrData[] = $totalTask > 0 ? round(($doneTask / $totalTask) * 100) : 0;
        }


        // --- 5. SOURCE ANALYSIS (Stacked) ---
        $sourceRaw = Lead::selectRaw('source, status, COUNT(*) as total')
            ->whereYear('created_at', $year)->groupBy('source', 'status')->get();

        $definedSources = ['Facebook Ads', 'Instagram', 'Tiktok', 'Website', 'Agent', 'Walk-in', 'Referral'];

        $sourceWon = array_fill_keys($definedSources, 0);
        $sourceProcess = array_fill_keys($definedSources, 0);
        $sourceLost = array_fill_keys($definedSources, 0);

        foreach ($sourceRaw as $row) {
            $srcKey = in_array($row->source, $definedSources) ? $row->source : 'Other';
            if ($srcKey === 'Other')
                continue;

            $st = strtolower($row->status);
            if (in_array($st, ['closing', 'converted', 'deal']))
                $sourceWon[$srcKey] += $row->total;
            elseif (in_array($st, ['hot', 'warm', 'negotiation']))
                $sourceProcess[$srcKey] += $row->total;
            else
                $sourceLost[$srcKey] += $row->total;
        }


        $charts = [
            'income' => [],
            'expense' => [],
            'hpp' => [], 
            'operational' => [],
            'jamaah' => [],
            'leads' => [],
            'closing' => []
        ];

        foreach ($months as $m) {
            $charts['income'][] = $incRaw[$m] ?? 0;
            $charts['expense'][] = $expRaw[$m] ?? 0;
            $charts['hpp'][] = $hppRaw[$m] ?? 0;
            $charts['operational'][] = $opsRaw[$m] ?? 0;
            $charts['jamaah'][] = $jamaahRaw[$m] ?? 0;
            $charts['leads'][] = $leadsRaw[$m] ?? 0;
            $charts['closing'][] = $jamaahRaw[$m] ?? 0;
        }

        return [
            'charts' => $charts,
            'content' => ['labels' => array_map('ucfirst', array_keys($platformCounts)), 'data' => array_values($platformCounts)],
            'hr' => ['labels' => $hrLabels, 'data' => $hrData],
            'sources' => [
                'labels' => $definedSources,
                'won' => array_values($sourceWon),
                'process' => array_values($sourceProcess),
                'lost' => array_values($sourceLost)
            ],
            'summary' => [
                'total_income' => $incTotal,
                'total_expense' => $expTotal,
                'total_hpp' => $hppTotal,
                'total_operational' => $opsTotal,
                'total_profit' => $incTotal - $expTotal,
                'total_leads' => $effectiveLeads,
                'total_jamaah' => $jamaahTotal,
                'income_growth' => $calcGrowth($incTotal, $incLastYear),
                'jamaah_growth' => $calcGrowth($jamaahTotal, $jamaahLastYear),
                'avg_conversion' => $conversionRate 
            ]
        ];
    }

    // 2. DATA BATCH REPORT
    public function getBatchReportDataProperty()
    {
        if (!$this->selectedBatchId)
            return null;

        $batch = UmrahPackage::with([
            'bookings.payments',
            'bookings.jamaah',
            'bookings.documentCheck',
            'bookings.inventoryMovements',
            'bookings.bookingFlights'
        ])->find($this->selectedBatchId);

        if (!$batch)
            return null;

        // 1. SEAT UTILIZATION
        $totalSeats = $batch->quota ?? 45;
        $totalBooked = $batch->bookings->where('status', '!=', 'cancelled')->count();
        $seatPercent = $totalSeats > 0 ? ($totalBooked / $totalSeats) * 100 : 0;
        $totalSeats = $batch->quota ?? 45;
        $totalBooked = $batch->bookings->where('status', '!=', 'cancelled')->count();
        $seatPercent = $totalSeats > 0 ? ($totalBooked / $totalSeats) * 100 : 0;

        $seatStatus = 'open';
        if ($seatPercent >= 100)
            $seatStatus = 'full';
        elseif ($seatPercent >= 80)
            $seatStatus = 'warning';

        // 2. FINANCE
        $totalOmset = $batch->bookings->where('status', '!=', 'cancelled')->sum('total_price');
        $totalPaid = $batch->bookings->where('status', '!=', 'cancelled')
            ->flatMap->payments->whereNotNull('verified_at')->sum('amount');
        $totalArrears = $totalOmset - $totalPaid;

        // 3. OPERATIONAL STATS (Summary)
        $validBookings = $batch->bookings->where('status', '!=', 'cancelled');

        // Hitung Dokumen (Paspor & Visa)
        $passportCount = $validBookings->filter(fn($b) => $b->documentCheck?->passport_status === 'received')->count();

        $visaCount = $validBookings->filter(fn($b) => $b->documentCheck?->visa_issued_at)->count();
        $logisticsCount = $validBookings->filter(fn($b) => $b->inventoryMovements->count() > 0)->count();

        $hotels = PackageHotel::where('umrah_package_id', $batch->id)
            ->orderBy('check_in', 'asc')
            ->get();

        // 4. FLIGHT LIST
        $flights = PackageFlight::where('umrah_package_id', $batch->id)
            ->orderBy('depart_at', 'asc')
            ->get();

        $allRundowns = $batch->rundowns;

        return [
            'info' => $batch,
            'seats' => [
                'total' => $totalSeats,
                'booked' => $totalBooked,
                'percent' => $seatPercent,
                'available' => max(0, $totalSeats - $totalBooked),
                'status' => $seatStatus
            ],
            'finance' => [
                'omset' => $totalOmset,
                'paid' => $totalPaid,
                'arrears' => $totalArrears,
            ],
            'stats' => [
                'pax_count' => $totalBooked,
                'passport' => $passportCount,
                'visa' => $visaCount,
                'logistics' => $logisticsCount
            ],
            'flights' => $flights,
            'hotels' => $hotels,
            'rundown' => [
                'pre' => $allRundowns->where('phase', 'pre')
                    ->sortBy([['date', 'asc'], ['time_start', 'asc']]),

                'during' => $allRundowns->where('phase', 'during')
                    ->sortBy([['day_number', 'asc'], ['time_start', 'asc']]),

                'post' => $allRundowns->where('phase', 'post')
                    ->sortBy([['date', 'asc'], ['time_start', 'asc']]),
            ]
        ];
    }

    public function getSelectedPackageProperty()
    {
        return UmrahPackage::find($this->selectedBatchId);
    }

    // DATA FINANCE
    public function getFinanceStatsProperty()
    {
        $now = now();
        return [
            'income_month' => Payment::whereMonth('created_at', $now->month)->whereNotNull('verified_at')->sum('amount'),
            'expense_month' => Expense::whereMonth('created_at', $now->month)->sum('amount'),
            'wallets' => OfficeWallet::all(),
        ];
    }

    public function getDailyFinanceRecapProperty()
    {
        $days = [];

        $period = CarbonPeriod::create($this->dateStart, $this->dateEnd);

        $dates = array_reverse(iterator_to_array($period));

        foreach ($dates as $date) {
            $dateVal = $date->format('Y-m-d');

            $income = Payment::whereDate('created_at', $dateVal)
                ->whereNotNull('verified_at')
                ->sum('amount');

            $expense = Expense::whereDate('created_at', $dateVal)
                ->sum('amount');

            $pettyWallet = OfficeWallet::where('name', 'like', '%Petty%')->first();
            $pettyBalance = $pettyWallet ? $pettyWallet->balance : 0;

            $isClosed = $date->lessThan(now()->startOfDay()) || ($date->isToday() && now()->hour >= 21);

            $days[] = [
                'date_obj' => $date,
                'date_str' => $dateVal,
                'income' => $income,
                'expense' => $expense,
                'petty_cash_balance' => $pettyBalance,
                'is_closed' => $isClosed,
            ];
        }

        return $days;
    }

    // DATA MARKETING
    public function getMarketingStatsProperty()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonth = $now->copy()->endOfMonth()->format('Y-m-d');

        $leadsPersonal = Lead::whereMonth('created_at', $now->month)->count();
        $leadsCorporate = CorporateLead::whereMonth('created_at', $now->month)->count();
        $totalLeads = $leadsPersonal + $leadsCorporate;

        $salesTeam = Employee::whereHas('departmentRel', fn($q) => $q->where('code', 'MKT'))
            ->withCount([
                'leads as personal_leads_count' => fn($q) => $q->whereMonth('created_at', $now->month),
                'corporateLeads as corp_leads_count' => fn($q) => $q->whereMonth('created_at', $now->month),
                'salesBookings as closing_count' => fn($q) => $q->whereMonth('created_at', $now->month)
            ])
            ->withSum([
                'salesTargets as monthly_target' => function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->where('start_date', '<=', $endOfMonth)
                        ->where('end_date', '>=', $startOfMonth);
                }
            ], 'target_jamaah')
            ->get()
            ->map(function ($sales) {
                $sales->total_leads_count = $sales->personal_leads_count + $sales->corp_leads_count;

                $target = $sales->monthly_target ?? 0;

                $sales->current_target = $target;

                $divider = $target > 0 ? $target : 1;

                $sales->achievement_percent = ($sales->closing_count / $divider) * 100;
                $sales->is_achieved = $sales->closing_count >= $target && $target > 0;

                return $sales;
            })
            ->sortByDesc('achievement_percent');

        $totalClosing = $salesTeam->sum('closing_count');

        $globalTarget = $salesTeam->sum('monthly_target');

        $conversionRate = $totalLeads > 0 ? ($totalClosing / $totalLeads) * 100 : 0;

        $topAgents = Agent::withCount(['bookings' => fn($q) => $q->whereMonth('created_at', $now->month)])
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get();

        $dormantAgents = Agent::whereDoesntHave('bookings', function ($q) {
            $q->where('created_at', '>=', now()->subMonths(3));
        })
            ->take(10)
            ->get();

        return [
            'leads_personal' => $leadsPersonal,
            'leads_corporate' => $leadsCorporate,
            'total_leads' => $totalLeads,
            'total_closing' => $totalClosing,
            'global_target' => $globalTarget,
            'conversion_rate' => $conversionRate,
            'sales_team' => $salesTeam,
            'top_agents' => $topAgents,
            'dormant_agents' => $dormantAgents,
        ];
    }

    // DATA MEDIA
    public function getMediaStatsProperty()
    {
        $now = now();

        return [
            'requests_pending' => ContentRequest::whereIn('status', ['pending', 'process'])->count(),

            'published_month' => ContentSchedule::where('status', 'published')
                ->whereMonth('scheduled_date', $now->month)
                ->whereYear('scheduled_date', $now->year)
                ->count(),

            'content_schedule' => ContentSchedule::query()
                ->whereMonth('scheduled_date', $now->month)
                ->whereYear('scheduled_date', $now->year)
                ->orderBy('scheduled_date', 'asc')
                ->get(),
        ];
    }

    // ACTION: BUKA MODAL LIST
    public function showMediaDetail($type)
    {
        $this->mediaListType = $type;

        if ($type === 'published') {
            $this->mediaListData = ContentRequest::where('status', 'published')
                ->whereMonth('updated_at', now()->month)
                ->latest('updated_at')
                ->get();
        } else {
            $this->mediaListData = MediaAsset::latest()
                ->take(12)
                ->get();
        }

        $this->showMediaListModal = true;
    }

    public function showLeadsDetail($type)
    {
        $this->leadsDetailType = $type;
        $now = now();

        if ($type === 'corporate') {
            $this->leadsData = CorporateLead::with('sales')
                ->whereMonth('created_at', $now->month)
                ->latest()
                ->get();
        } else {
            $this->leadsData = Lead::with('sales')
                ->whereMonth('created_at', $now->month)
                ->latest()
                ->get();
        }

        $this->showLeadsModal = true;
    }

    public function getHrStatsProperty()
    {
        $now = now();

        $employees = Employee::where('status', '!=', 'resign')
            ->with('departmentRel')
            ->get()
            ->map(function ($emp) use ($now) {

                $dailyTasks = Task::where('employee_id', $emp->id)
                    ->whereDate('created_at', $now->format('Y-m-d'))
                    ->get();

                $emp->daily_total = $dailyTasks->count();
                $emp->daily_done = $dailyTasks->where('status', 'completed')->count();

                $monthlyTasks = Task::where('employee_id', $emp->id)
                    ->whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)
                    ->get();

                $mTotal = $monthlyTasks->count();
                $mDone = $monthlyTasks->where('status', 'completed')->count();
                $emp->monthly_percent = $mTotal > 0 ? round(($mDone / $mTotal) * 100) : 0;

                return $emp;
            })
            ->sortByDesc('monthly_percent');

        $totalEmployees = $employees->count();
        $activeTasksToday = Task::whereDate('created_at', $now->format('Y-m-d'))->count();
        $avgPerformance = $employees->avg('monthly_percent');

        return [
            'employees' => $employees,
            'total_employees' => $totalEmployees,
            'active_tasks' => $activeTasksToday,
            'avg_performance' => $avgPerformance
        ];
    }

    // ACTION: LIHAT DETAIL TUGAS (MODAL)
    public function viewEmployeeTasks($employeeId)
    {
        $emp = Employee::find($employeeId);
        $this->selectedEmployeeName = $emp->full_name;

        $this->employeeTasks = Task::with('template')
            ->where('employee_id', $employeeId)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->get();

        $this->showHrModal = true;
    }

    public function downloadDailyReport($dateStr)
    {
        $date = Carbon::parse($dateStr);

        $todayIncome = Payment::whereDate('created_at', $date)
            ->whereNotNull('verified_at')
            ->sum('amount');

        $laciBalance = OfficeWallet::where('type', 'cashier')->sum('balance');
        $pettyBalance = OfficeWallet::where('type', 'petty_cash')->sum('balance');

        $bankWallets = OfficeWallet::where('type', 'bank')
            ->withSum(['payments' => fn($q) => $q->whereDate('created_at', $date)->whereNotNull('verified_at')], 'amount')
            ->get();

        $expenses = Expense::with(['category', 'wallet', 'approver'])
            ->whereDate('transaction_date', $date)
            ->where('status', 'approved')
            ->get();

        $totalExpense = $expenses->sum('amount');
        $expenseOperasional = $expenses->filter(fn($e) => $e->wallet && $e->wallet->type === 'petty_cash')->sum('amount');
        $expenseHpp = $expenses->filter(fn($e) => $e->wallet && $e->wallet->type !== 'petty_cash')->sum('amount');

        $pdf = Pdf::loadView('pdf.daily_report', compact(
            'date',
            'todayIncome',
            'laciBalance',
            'pettyBalance',
            'bankWallets',
            'expenses',
            'totalExpense',
            'expenseOperasional',
            'expenseHpp'
        ));

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Laporan-Harian-' . $date->format('Y-m-d') . '.pdf');
    }

    // 1. MANIFEST JAMAAH
    public function exportPdf()
    {
        if (!$this->selectedBatchId)
            return;

        $package = $this->selectedPackage;
        $manifestData = $package->bookings()
            ->with(['jamaah', 'documentCheck', 'bookingFlights'])
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sortBy(fn($b) => $b->jamaah->name);

        $data = [
            'package' => $package,
            'manifest' => $manifestData
        ];

        $pdf = Pdf::loadView('pdf.manifest_print', $data);
        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Manifest-' . Str::slug($package->name) . '.pdf');
    }

    // 2. ROOMING LIST & HOTEL
    public function exportRoomingPdf()
    {
        if (!$this->selectedBatchId)
            return;

        $assignments = RoomAssignment::with(['booking.jamaah'])
            ->where('umrah_package_id', $this->selectedBatchId)
            ->get()
            ->groupBy('hotel_name');

        $pdf = Pdf::loadView('pdf.rooming_list', [
            'package' => $this->selectedPackage,
            'groupedAssignments' => $assignments
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Rooming-List-' . Str::slug($this->selectedPackage->name) . '.pdf');
    }

    // 3. FLIGHT MANIFEST
    public function exportFlightPdf()
    {
        if (!$this->selectedBatchId)
            return;

        $allFlights = PackageFlight::where('umrah_package_id', $this->selectedBatchId)
            ->orderBy('depart_at', 'asc')
            ->get();

        if ($allFlights->isEmpty()) {
            Notification::make()->title('Belum ada jadwal penerbangan!')->warning()->send();
            return;
        }

        $bookings = $this->selectedPackage->bookings()
            ->with(['jamaah', 'documentCheck', 'bookingFlights'])
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sortBy(fn($b) => $b->jamaah->name);

        $data = [
            'package' => $this->selectedPackage,
            'flights' => $allFlights,
            'bookings' => $bookings,
        ];

        $pdf = Pdf::loadView('pdf.flight_manifest_all', $data);
        $pdf->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Flight-Manifest-All-' . Str::slug($this->selectedPackage->name) . '.pdf');
    }
};
?>

<div class="flex flex-col h-full w-full relative bg-slate-50 dark:bg-[#09090b]" 
     x-data="{ mobileMenuOpen: false }">
    <div class="absolute -bottom-24 -right-24 w-128 h-128 opacity-40 dark:opacity-40 pointer-events-none transform">
        <img src="{{ asset('images/icons/kabah1.png') }}" alt="Kabah Decoration" class="w-full h-full object-contain">
    </div>

    <nav 
        class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md px-4 py-2.5 flex justify-between items-center border-b border-slate-200 dark:border-white/5 shrink-0 z-50 relative"
        style="
            background-image: url('/images/ornaments/arabesque.png');
            background-repeat: repeat;
            background-size: 150px 150px;
        ">
        
        <div class="flex items-center gap-4">
    
            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="md:hidden p-2 -ml-2 text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800 rounded-lg transition">
                <x-heroicon-o-bars-3 class="w-6 h-6" />
            </button>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                    <x-heroicon-s-chart-pie class="w-6 h-6" />
                </div>

                <div class="flex flex-col">
                    <span class="font-black text-sm md:text-base tracking-tight leading-none uppercase">
                        Report <span class="text-indigo-600 dark:text-indigo-400">Center</span>
                    </span>
                    
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-zinc-500 tracking-widest uppercase">
                            Executive Dashboard
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex items-center gap-2 md:gap-4">
            <button @click="toggleTheme()" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-all duration-300">
                <x-heroicon-s-moon class="w-5 h-5" x-show="!darkMode" />
                <x-heroicon-s-sun class="w-6 h-6 text-indigo-500" x-show="darkMode" x-cloak />
            </button>

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center gap-2 p-1 pr-3 rounded-full bg-slate-100 dark:bg-zinc-800 hover:ring-2 hover:ring-indigo-500/30 transition-all cursor-pointer">
                    <div class="h-7 w-7 rounded-full bg-indigo-600 flex items-center justify-center text-white font-black text-[10px] shadow-sm">
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
            
            <button wire:click="$set('activeTab', 'analytics')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'analytics' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'analytics' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-indigo-500/10' }}">
                    <x-heroicon-s-chart-bar-square class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Analisa</span>
                @if($activeTab === 'analytics') <div class="absolute -right-[25px] w-1.5 h-8 bg-indigo-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="$set('activeTab', 'batch_report')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'batch_report' ? 'text-purple-600 dark:text-purple-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'batch_report' ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-purple-500/10' }}">
                    <x-heroicon-s-cube class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Batch</span>
                @if($activeTab === 'batch_report') <div class="absolute -right-[25px] w-1.5 h-8 bg-purple-600 rounded-l-full"></div> @endif
            </button>
            
            <button wire:click="$set('activeTab', 'finance')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'finance' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'finance' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-emerald-500/10' }}">
                    <x-heroicon-s-banknotes class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Finance</span>
                @if($activeTab === 'finance') <div class="absolute -right-[25px] w-1.5 h-8 bg-emerald-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="$set('activeTab', 'marketing')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'marketing' ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'marketing' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-blue-500/10' }}">
                    <x-heroicon-s-presentation-chart-line class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Sales</span>
                @if($activeTab === 'marketing') <div class="absolute -right-[25px] w-1.5 h-8 bg-blue-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="$set('activeTab', 'media')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'media' ? 'text-pink-600 dark:text-pink-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'media' ? 'bg-pink-600 text-white shadow-lg shadow-pink-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-pink-500/10' }}">
                    <x-heroicon-s-swatch class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">Media</span>
                @if($activeTab === 'media') <div class="absolute -right-[25px] w-1.5 h-8 bg-pink-600 rounded-l-full"></div> @endif
            </button>

            <button wire:click="$set('activeTab', 'hr')"
                class="group relative flex flex-col items-center gap-1.5 transition-all duration-300 {{ $activeTab === 'hr' ? 'text-orange-600 dark:text-orange-400' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $activeTab === 'hr' ? 'bg-orange-600 text-white shadow-lg shadow-orange-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-orange-500/10' }}">
                    <x-heroicon-s-users class="w-6 h-6" />
                </div>
                <span class="text-[9px] uppercase font-black tracking-tighter">SDM</span>
                @if($activeTab === 'hr') <div class="absolute -right-[25px] w-1.5 h-8 bg-orange-600 rounded-l-full"></div> @endif
            </button>

            <div class="absolute -bottom-18 -left-24 w-48 h-48 opacity-15 pointer-events-none z-0">
                <img src="{{ asset('images/ornaments/ornamen1.png') }}" 
                    alt="Ornamen" 
                    class="w-full h-full object-contain transform rotate-90">
            </div>

        </aside>

        <div x-show="mobileMenuOpen" 
             @click="mobileMenuOpen = false"
             x-transition.opacity
             class="fixed inset-0 bg-slate-900/80 z-50 md:hidden backdrop-blur-sm">
        </div>

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
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-lg shadow-indigo-500/30">
                        E
                    </div>
                    <div>
                        <span class="font-black text-lg text-slate-900 dark:text-white tracking-tight leading-none block">Executive</span>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-500 uppercase tracking-widest font-bold">Dashboard Menu</span>
                    </div>
                </div>
                <button @click="mobileMenuOpen = false" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 transition">
                    <x-heroicon-m-x-mark class="w-6 h-6" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto no-scrollbar p-4 flex flex-col gap-2">
                
                <button wire:click="$set('activeTab', 'analytics'); mobileMenuOpen = false"
                    class="flex items-center gap-4 px-4 py-4 rounded-xl transition-all font-bold text-sm w-full text-left {{ $activeTab === 'analytics' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 ring-1 ring-indigo-500/20 shadow-sm' : 'text-slate-500 hover:bg-slate-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-chart-bar-square class="w-6 h-6" /> <span>Business Analytics</span>
                </button>

                <button wire:click="$set('activeTab', 'batch_report'); mobileMenuOpen = false"
                    class="flex items-center gap-4 px-4 py-4 rounded-xl transition-all font-bold text-sm w-full text-left {{ $activeTab === 'batch_report' ? 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400 ring-1 ring-purple-500/20 shadow-sm' : 'text-slate-500 hover:bg-slate-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-cube class="w-6 h-6" /> <span>Batch Report</span>
                </button>

                <button wire:click="$set('activeTab', 'finance'); mobileMenuOpen = false"
                    class="flex items-center gap-4 px-4 py-4 rounded-xl transition-all font-bold text-sm w-full text-left {{ $activeTab === 'finance' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 ring-1 ring-emerald-500/20 shadow-sm' : 'text-slate-500 hover:bg-slate-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-banknotes class="w-6 h-6" /> <span>Financial Report</span>
                </button>

                <button wire:click="$set('activeTab', 'marketing'); mobileMenuOpen = false"
                    class="flex items-center gap-4 px-4 py-4 rounded-xl transition-all font-bold text-sm w-full text-left {{ $activeTab === 'marketing' ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 ring-1 ring-blue-500/20 shadow-sm' : 'text-slate-500 hover:bg-slate-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-presentation-chart-line class="w-6 h-6" /> <span>Marketing & Sales</span>
                </button>

                <button wire:click="$set('activeTab', 'media'); mobileMenuOpen = false"
                    class="flex items-center gap-4 px-4 py-4 rounded-xl transition-all font-bold text-sm w-full text-left {{ $activeTab === 'media' ? 'bg-pink-50 text-pink-700 dark:bg-pink-500/10 dark:text-pink-400 ring-1 ring-pink-500/20 shadow-sm' : 'text-slate-500 hover:bg-slate-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-swatch class="w-6 h-6" /> <span>Media & Content</span>
                </button>

                <button wire:click="$set('activeTab', 'hr'); mobileMenuOpen = false"
                    class="flex items-center gap-4 px-4 py-4 rounded-xl transition-all font-bold text-sm w-full text-left {{ $activeTab === 'hr' ? 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 ring-1 ring-orange-500/20 shadow-sm' : 'text-slate-500 hover:bg-slate-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-users class="w-6 h-6" /> <span>Human Resources</span>
                </button>

                

            </div>
            
            <div class="p-6 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5">
                <p class="text-[10px] text-center text-slate-400 uppercase font-bold tracking-widest">
                    Rawabi System v1.0 <br>
                    <span class="opacity-50 font-normal">© {{ date('Y') }} All Rights Reserved</span>
                </p>
            </div>
            <div class="absolute -bottom-24 -left-48 w-96 h-96 opacity-15 pointer-events-none z-0">
                <img src="{{ asset('images/ornaments/ornamen1.png') }}" 
                    alt="Ornamen" 
                    class="w-full h-full object-contain transform rotate-90">
            </div>
            
        </div>

        <main class="flex-1 h-full overflow-y-auto custom-scrollbar p-4 md:px-8 pb-24 md:pb-8 pt-0 relative">
            
            <div class="mb-8 sticky top-0 bg-slate-50/90 dark:bg-zinc-950/90 backdrop-blur-md z-20 py-4 -mx-4 px-4 md:-mx-8 md:px-8 border-b border-transparent transition-all"
                :class="{ 'border-slate-200 dark:border-white/5 shadow-sm': $el.closest('.overflow-y-auto').scrollTop > 0 }">
                
                <div class="flex flex-row items-center justify-between gap-4">
                    
                    <div class="relative">
                        
                    </div>

                    <div class="flex items-center gap-3">
                        <div class=" flex items-center gap-2 text-[10px] font-black uppercase tracking-widest bg-white dark:bg-zinc-900 px-4 py-2 rounded-full border border-slate-200 dark:border-white/10 shadow-sm">
                            <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span class="text-slate-600 dark:text-zinc-300">Live Data Sync</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                @if($activeTab === 'analytics')
                <div class="animate-fade-in space-y-8">

                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-lg border border-slate-100 dark:border-white/5 relative overflow-hidden">
                        <div class="relative z-10">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                                <div class="p-2 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl text-indigo-600 dark:text-indigo-400">
                                    <x-heroicon-s-chart-bar-square class="w-6 h-6" />
                                </div>
                                Business Analytics
                            </h2>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-2 ml-14 uppercase tracking-widest">
                                Yearly Performance Review
                            </p>
                        </div>
                        
                        <div class="mt-4 md:mt-0 relative z-10">
                            <div class="relative group">
                                <select wire:model.live="analyticsYear" 
                                        class="pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-black text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all appearance-none cursor-pointer uppercase tracking-wider">
                                    @foreach(range(now()->year, now()->year - 4) as $y)
                                        <option value="{{ $y }}" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">Tahun {{ $y }}</option>
                                    @endforeach
                                </select>
                                <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>

                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-white/5 relative overflow-hidden"
                            x-data="{
                                chart: null,
                                init() {
                                    this.$nextTick(() => {
                                        const stats = @js($this->analyticsStats);
                                        const data = stats.charts;
                                        if (this.chart) { this.chart.destroy(); }

                                        const options = {
                                            series: [
                                                { name: 'Income', data: data.income },
                                                { name: 'HPP', data: data.hpp },
                                                { name: 'Operational', data: data.operational }
                                            ],
                                            chart: { type: 'area', height: 450, toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
                                            colors: ['#10b981', '#f59e0b', '#ef4444'],
                                            dataLabels: { enabled: false },
                                            stroke: { curve: 'smooth', width: 3 },
                                            xaxis: { 
                                                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], 
                                                labels: { style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 700 } }, 
                                                axisBorder: { show: false }, axisTicks: { show: false } 
                                            },
                                            yaxis: { 
                                                labels: { 
                                                    style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 }, 
                                                    formatter: (val) => (val/1000000).toFixed(0) + 'M' 
                                                } 
                                            },
                                            grid: { borderColor: document.documentElement.classList.contains('dark') ? '#27272a' : '#f1f5f9', strokeDashArray: 4 },
                                            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                                            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
                                            legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'inherit', fontWeight: 600 }
                                        };

                                        if(this.$refs.financialChart) { 
                                            this.chart = new ApexCharts(this.$refs.financialChart, options); 
                                            this.chart.render(); 
                                        }
                                    });
                                }
                            }"
                            x-init="init()">

                            <div class="flex justify-between items-start mb-6 px-2">
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Financial Breakdown</h3>
                                    <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest mt-1">Income vs Expense Trend</p>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center gap-2 justify-end {{ $this->analyticsStats['summary']['income_growth'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                                        <span class="text-2xl font-black tracking-tight">{{ abs($this->analyticsStats['summary']['income_growth']) }}%</span>
                                        @if($this->analyticsStats['summary']['income_growth'] >= 0)
                                            <x-heroicon-s-arrow-trending-up class="w-6 h-6" />
                                        @else
                                            <x-heroicon-s-arrow-trending-down class="w-6 h-6" />
                                        @endif
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Growth (YoY)</p>
                                </div>
                            </div>

                            <div x-ref="financialChart" class="w-full h-[360px]"></div>
                        </div>

                        <div class="space-y-6 flex flex-col h-full">

                            <div class="grid grid-cols-2 gap-4">
    
                                <div class="bg-white dark:bg-zinc-900 p-5 rounded-[2rem] border border-slate-100 dark:border-white/5 shadow-sm group hover:border-emerald-200 transition-colors">
                                    <div class="flex items-center gap-2 mb-1 opacity-60">
                                        <x-heroicon-s-arrow-down-left class="w-3 h-3 text-emerald-500" />
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Total Income</p>
                                    </div>
                                    <p class="text-lg font-black text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform origin-left">
                                        {{ number_format($this->analyticsStats['summary']['total_income'] / 1000000, 0) }} M
                                    </p>
                                </div>

                                <div class="bg-white dark:bg-zinc-900 p-5 rounded-[2rem] border border-slate-100 dark:border-white/5 shadow-sm group hover:border-red-200 transition-colors">
                                    <div class="flex items-center gap-2 mb-1 opacity-60">
                                        <x-heroicon-s-arrow-up-right class="w-3 h-3 text-red-500" />
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Total Expense</p>
                                    </div>
                                    <p class="text-lg font-black text-red-500 group-hover:scale-105 transition-transform origin-left">
                                        {{ number_format($this->analyticsStats['summary']['total_expense'] / 1000000, 0) }} M
                                    </p>
                                </div>

                                <div class="bg-orange-50 dark:bg-orange-500/5 p-5 rounded-[2rem] border border-orange-100 dark:border-orange-500/10 shadow-sm group">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="p-1 bg-orange-100 dark:bg-orange-500/20 rounded-md">
                                            <x-heroicon-s-shopping-bag class="w-3 h-3 text-orange-600 dark:text-orange-400" />
                                        </div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-orange-600/60 dark:text-orange-400/60">HPP (Modal)</p>
                                    </div>
                                    <p class="text-base font-black text-orange-600 dark:text-orange-400 group-hover:scale-105 transition-transform origin-left">
                                        Rp {{ number_format($this->analyticsStats['summary']['total_hpp'] / 1000000, 0) }} M
                                    </p>
                                </div>

                                <div class="bg-rose-50 dark:bg-rose-500/5 p-5 rounded-[2rem] border border-rose-100 dark:border-rose-500/10 shadow-sm group">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="p-1 bg-rose-100 dark:bg-rose-500/20 rounded-md">
                                            <x-heroicon-s-building-office class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                                        </div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-rose-600/60 dark:text-rose-400/60">Operasional</p>
                                    </div>
                                    <p class="text-base font-black text-rose-600 dark:text-rose-400 group-hover:scale-105 transition-transform origin-left">
                                        Rp {{ number_format($this->analyticsStats['summary']['total_operational'] / 1000000, 0) }} M
                                    </p>
                                </div>

                            </div>

                            <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] border border-slate-100 dark:border-white/5 flex-1 flex flex-col items-center justify-center relative overflow-hidden"
                                x-data="{
                                    chart: null,
                                    init() {
                                        this.$nextTick(() => {
                                            const stats = @js($this->analyticsStats);
                                            const income = Number(stats.summary.total_income) || 0;
                                            const expense = (Number(stats.summary.total_hpp) || 0) + (Number(stats.summary.total_operational) || 0);
                                            const ratio = income > 0 ? (expense / income) * 100 : 0;
                                            const safeRatio = Math.min(Math.max(ratio, 0), 100);
                                            const color = safeRatio < 60 ? '#10b981' : (safeRatio < 80 ? '#f59e0b' : '#ef4444');

                                            if (this.chart) { this.chart.destroy(); }

                                            const options = {
                                                series: [expense, Math.max(income - expense, 0)],
                                                chart: { type: 'donut', height: '100%', background: 'transparent' },
                                                labels: ['Expense', 'Remaining'],
                                                colors: [color, '#10b981'],
                                                dataLabels: { enabled: false },
                                                legend: { show: false },
                                                plotOptions: {
                                                    pie: {
                                                        donut: {
                                                            size: '70%',
                                                            labels: {
                                                                show: true,
                                                                total: {
                                                                    show: true,
                                                                    label: 'Expense',
                                                                    fontSize: '10px',
                                                                    fontWeight: 900,
                                                                    color: '#94a3b8',
                                                                    formatter: () => safeRatio.toFixed(1) + '%'
                                                                }
                                                            }
                                                        }
                                                    }
                                                },
                                                tooltip: { y: { formatter: val => 'Rp ' + (val / 1_000_000).toFixed(1) + ' Jt' } }
                                            };
                                            this.chart = new ApexCharts(this.$refs.expenseDonut, options);
                                            this.chart.render();
                                        });
                                    }
                                }"
                                x-init="init()">
                                
                                <div class="w-full flex justify-between items-center mb-2 px-2">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Expense Ratio</p>
                                    <x-heroicon-s-information-circle class="w-4 h-4 text-slate-300" />
                                </div>

                                <div x-ref="expenseDonut" class="flex-1 w-full min-h-[160px]"></div>
                                
                                <p class="text-[10px] font-bold text-center px-4 py-2 rounded-xl bg-slate-50 dark:bg-white/5 text-slate-500 dark:text-zinc-400 mt-2">
                                    dari total income digunakan untuk biaya
                                </p>
                            </div>

                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5"
                            x-data="{
                                chart: null,
                                init() {
                                    this.$nextTick(() => {
                                        const data = @js($this->analyticsStats);
                                        if(this.chart) this.chart.destroy();
                                        
                                        const options = {
                                            series: [{ name: 'Leads Masuk', data: data.charts.leads }, { name: 'Closing', data: data.charts.closing }],
                                            chart: { type: 'line', height: 280, toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
                                            stroke: { width: [3, 3], curve: 'smooth' },
                                            colors: ['#cbd5e1', '#3b82f6'],
                                            xaxis: { 
                                                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], 
                                                labels: { style: { colors: '#94a3b8', fontSize: '10px' } }, 
                                                axisBorder: { show: false }, axisTicks: { show: false } 
                                            },
                                            yaxis: { labels: { style: { colors: '#94a3b8' } } },
                                            grid: { borderColor: document.documentElement.classList.contains('dark') ? '#27272a' : '#f1f5f9', strokeDashArray: 4 },
                                            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                                        };
                                        this.chart = new ApexCharts(this.$refs.marketingChart, options);
                                        this.chart.render();
                                    });
                                }
                            }" 
                            x-init="init()">
                            <div class="mb-6 px-2">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Marketing Trend</h3>
                                <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Leads vs Closing Performance</p>
                            </div>
                            <div x-ref="marketingChart" class="w-full h-72"></div>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 flex flex-col justify-between relative overflow-hidden"
                            x-data="{
                                chart: null,
                                init() {
                                    this.$nextTick(() => {
                                        const stats = @js($this->analyticsStats);
                                        let rate = parseFloat(stats?.summary?.avg_conversion ?? 0);
                                        if (rate <= 1) rate = rate * 100;
                                        rate = Math.min(Math.max(rate, 0), 100);

                                        if (this.chart) this.chart.destroy();

                                        const options = {
                                            series: [Number(rate.toFixed(1))],
                                            chart: { type: 'radialBar', height: 220, background: 'transparent' },
                                            plotOptions: {
                                                radialBar: {
                                                    startAngle: -135, endAngle: 135,
                                                    hollow: { size: '60%', margin: 15 },
                                                    track: { background: document.documentElement.classList.contains('dark') ? '#333' : '#f3f4f6' },
                                                    dataLabels: {
                                                        name: { show: true, fontSize: '10px', color: '#888', offsetY: -10 },
                                                        value: { show: true, fontSize: '24px', fontWeight: '900', color: document.documentElement.classList.contains('dark') ? '#fff' : '#1e293b', offsetY: 5, formatter: val => val + '%' }
                                                    }
                                                }
                                            },
                                            labels: ['Conversion Rate'],
                                            colors: ['#3b82f6'],
                                            stroke: { lineCap: 'round' },
                                            fill: { type: 'gradient', gradient: { type: 'horizontal', gradientToColors: ['#8b5cf6'], stops: [0, 100] } }
                                        };
                                        this.chart = new ApexCharts(this.$refs.conversionChart, options);
                                        this.chart.render();
                                    });
                                }
                            }"
                            x-init="init()">
                            
                            <div class="relative z-10 px-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="p-1.5 bg-blue-100 dark:bg-blue-500/20 rounded-lg text-blue-600 dark:text-blue-400">
                                        <x-heroicon-s-funnel class="w-4 h-4" />
                                    </div>
                                    <h3 class="font-black text-slate-900 dark:text-white text-sm uppercase tracking-tight">Conversion</h3>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest pl-9">Leads Effectiveness</p>
                            </div>

                            <div x-ref="conversionChart" class="w-full flex justify-center -mt-4 min-h-[220px] relative z-10"></div>

                            <div class="grid grid-cols-2 gap-4 mt-[-20px] relative z-10">
                                <div class="text-center p-3 bg-slate-50 dark:bg-white/5 rounded-2xl">
                                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Leads</span>
                                    <span class="block font-black text-slate-900 dark:text-white text-sm">
                                        {{ number_format($this->analyticsStats['summary']['total_leads']) }}
                                    </span>
                                </div>
                                <div class="text-center p-3 bg-slate-50 dark:bg-white/5 rounded-2xl">
                                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Closing</span>
                                    <span class="block font-black text-blue-600 text-sm">
                                        {{ number_format($this->analyticsStats['summary']['total_jamaah']) }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 flex flex-col"
                                x-data="{
                                    chart: null,
                                    init() {
                                        this.$nextTick(() => {
                                            const allData = @js($this->analyticsStats);
                                            const data = allData.sources;
                                            if(this.chart) this.chart.destroy();

                                            const options = {
                                                series: [
                                                    { name: 'Deal', data: data.won },
                                                    { name: 'Process', data: data.process },
                                                    { name: 'Lost', data: data.lost }
                                                ],
                                                chart: { type: 'bar', height: 320, stacked: true, toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
                                                colors: ['#10b981', '#f59e0b', '#94a3b8'],
                                                plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '40%' } },
                                                dataLabels: { enabled: false },
                                                stroke: { width: 1, colors: ['#fff'] },
                                                xaxis: { 
                                                    categories: data.labels, 
                                                    labels: { style: { colors: '#9ca3af', fontSize: '10px' } }, 
                                                    axisBorder: { show: false }, axisTicks: { show: false } 
                                                },
                                                yaxis: { labels: { style: { colors: '#9ca3af' } } },
                                                tooltip: { theme: 'dark' },
                                                legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#9ca3af' } },
                                                grid: { borderColor: document.documentElement.classList.contains('dark') ? '#27272a' : '#f1f5f9', strokeDashArray: 4 },
                                                theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                                            };
                                            this.chart = new ApexCharts(this.$refs.sourceChart, options);
                                            this.chart.render();
                                        });
                                    }
                                }" 
                                x-init="init()">
                            
                            <div class="mb-4 px-2">
                                <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Lead Source Quality</h3>
                                <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Sumber Leads vs Closing</p>
                            </div>
                            
                            <div class="flex-1 w-full relative min-h-[300px]">
                                <div x-ref="sourceChart" class="absolute inset-0 w-full h-full"></div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            
                            <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5"
                                    x-data="{
                                        chart: null,
                                        init() {
                                            this.$nextTick(() => {
                                                const data = @js($this->analyticsStats);
                                                if(this.chart) this.chart.destroy();

                                                const options = {
                                                    series: [{ name: 'Jamaah', data: data.charts.jamaah }],
                                                    chart: { type: 'bar', height: 200, toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
                                                    colors: ['#8b5cf6'],
                                                    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
                                                    xaxis: { 
                                                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], 
                                                        labels: { show: true, style: { colors: '#9ca3af', fontSize: '10px' } }, // Show True
                                                        axisBorder: { show: false }, 
                                                        axisTicks: { show: false } 
                                                    },
                                                    yaxis: { show: false },
                                                    grid: { show: true, borderColor: '#333', strokeDashArray: 4, padding: { left: 10, right: 0 } }, // Show Grid
                                                    tooltip: { theme: 'dark' },
                                                    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                                                };
                                                this.chart = new ApexCharts(this.$refs.growthChart, options);
                                                this.chart.render();
                                            });
                                        }
                                    }" 
                                    x-init="init()">
                                
                                <div class="flex justify-between items-start mb-2 px-2">
                                    <div>
                                        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Growth Jamaah</h3>
                                        <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Pax Monthly Trend</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-xl font-black text-indigo-600 dark:text-indigo-400">{{ $this->analyticsStats['summary']['total_jamaah'] }} Pax</span>
                                        <span class="text-[10px] font-black {{ $this->analyticsStats['summary']['jamaah_growth'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                                            {{ $this->analyticsStats['summary']['jamaah_growth'] >= 0 ? '+' : '' }}{{ $this->analyticsStats['summary']['jamaah_growth'] }}% YoY
                                        </span>
                                    </div>
                                </div>
                                
                                <div x-ref="growthChart" class="w-full h-[200px]"></div>
                            </div>

                            <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5"
                                    x-data="{
                                        chart: null,
                                        init() {
                                            this.$nextTick(() => {
                                                const data = @js($this->analyticsStats);
                                                if(this.chart) this.chart.destroy();

                                                const options = {
                                                    series: [{ name: 'Selesai %', data: data.hr.data }],
                                                    chart: { type: 'bar', height: 200, toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
                                                    colors: ['#0ea5e9'],
                                                    plotOptions: { 
                                                        bar: { 
                                                            borderRadius: 4, 
                                                            horizontal: true,
                                                            barHeight: '50%',
                                                            distributed: true 
                                                        } 
                                                    },
                                                    xaxis: { 
                                                        categories: data.hr.labels, 
                                                        labels: { show: true, style: { colors: '#9ca3af', fontSize: '10px' } }
                                                    },
                                                    yaxis: { 
                                                        max: 100,
                                                        labels: { show: true, style: { colors: '#9ca3af', fontSize: '10px', fontWeight: 700 } } 
                                                    },
                                                    grid: { show: false },
                                                    legend: { show: false },
                                                    tooltip: { theme: 'dark', y: { formatter: val => val + '%' } },
                                                    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                                                };
                                                this.chart = new ApexCharts(this.$refs.hrChart, options);
                                                this.chart.render();
                                            });
                                        }
                                    }" 
                                    x-init="init()">
                                
                                <div class="mb-2 px-2">
                                    <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Team Productivity</h3>
                                    <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Task Completion Rate</p>
                                </div>
                                
                                <div x-ref="hrChart" class="w-full h-[200px]"></div>
                            </div>

                        </div>
                    </div>

                </div>
                @endif

                @if($activeTab === 'batch_report')
                <div class="animate-fade-in space-y-8">
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-lg border border-slate-100 dark:border-white/5 relative overflow-hidden">
                        <div class="relative z-10">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                                <div class="p-2 bg-purple-50 dark:bg-purple-500/10 rounded-xl text-purple-600 dark:text-purple-400">
                                    <x-heroicon-s-cube class="w-6 h-6" />
                                </div>
                                Batch Report
                            </h2>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-2 ml-14 uppercase tracking-widest">
                                Keberangkatan & Operasional
                            </p>
                        </div>
                        
                        <div class="mt-4 md:mt-0 relative z-10 w-full md:w-72">
                            <div class="relative group">
                                <select wire:model.live="selectedBatchId" 
                                        class="w-full pl-4 pr-10 py-3 bg-slate-50 dark:bg-zinc-800 border-2 border-slate-100 dark:border-white/5 rounded-xl text-sm font-black text-slate-700 dark:text-zinc-200 focus:border-purple-500/50 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all appearance-none cursor-pointer uppercase tracking-wider">
                                    <option value="" class="text-slate-500">-- Pilih Batch --</option>
                                    @foreach(UmrahPackage::latest()->take(10)->get() as $pkg)
                                        <option value="{{ $pkg->id }}" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                            {{ $pkg->name }} ({{ Carbon::parse($pkg->departure_date)->format('d M') }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>

                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-purple-500/5 to-indigo-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                    </div>

                    @if($this->batchReportData)
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 relative overflow-hidden flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Seat Utilization</h3>
                                        <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Keterisian Kursi</p>
                                    </div>
                                    @if($this->batchReportData['seats']['status'] === 'full')
                                        <span class="px-3 py-1 bg-red-100 text-red-600 rounded-lg text-[10px] font-black uppercase tracking-widest">Full Booked</span>
                                    @elseif($this->batchReportData['seats']['status'] === 'warning')
                                        <span class="px-3 py-1 bg-amber-100 text-amber-600 rounded-lg text-[10px] font-black uppercase tracking-widest">Hampir Penuh</span>
                                    @else
                                        <span class="px-3 py-1 bg-emerald-100 text-emerald-600 rounded-lg text-[10px] font-black uppercase tracking-widest">Open Seat</span>
                                    @endif
                                </div>

                                <div class="flex items-baseline gap-1 mb-6">
                                    <span class="text-4xl font-black text-slate-900 dark:text-white">{{ $this->batchReportData['seats']['booked'] }}</span>
                                    <span class="text-lg font-bold text-slate-400">/ {{ $this->batchReportData['seats']['total'] }} Pax</span>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                                    <span>Progress</span>
                                    <span>{{ number_format($this->batchReportData['seats']['percent'], 0) }}% Terisi</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-zinc-800 h-3 rounded-full overflow-hidden">
                                    @php
                                        $pct = $this->batchReportData['seats']['percent'];
                                        $color = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                                    @endphp
                                    <div class="{{ $color }} h-full transition-all duration-1000 ease-out rounded-full relative" style="width: {{ $pct }}%">
                                        <div class="absolute inset-0 bg-white/20 animate-[pulse_2s_infinite]"></div>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between items-center">
                                    <p class="text-xs font-bold text-slate-500">Sisa Kuota: <span class="text-slate-900 dark:text-white">{{ $this->batchReportData['seats']['available'] }} Seat</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                            
                            <div class="bg-indigo-50 dark:bg-indigo-500/10 p-6 rounded-[2.5rem] border border-indigo-100 dark:border-indigo-500/20 flex flex-col justify-center">
                                <div class="flex items-center gap-2 mb-2">
                                    <x-heroicon-s-currency-dollar class="w-5 h-5 text-indigo-500" />
                                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Total Omset</p>
                                </div>
                                <h3 class="text-2xl md:text-6xl font-black text-indigo-900 dark:text-indigo-100">
                                    <span class="text-lg opacity-60">Rp</span> {{ number_format($this->batchReportData['finance']['omset'] / 1000000, 1) }} <span class="text-sm md:text-lg">Jt</span>
                                </h3>
                            </div>

                            <div class="bg-emerald-50 dark:bg-emerald-500/10 p-6 rounded-[2.5rem] border border-emerald-100 dark:border-emerald-500/20 flex flex-col justify-center">
                                <div class="flex items-center gap-2 mb-2">
                                    <x-heroicon-s-check-circle class="w-5 h-5 text-emerald-500" />
                                    <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">Sudah Bayar</p>
                                </div>
                                <h3 class="text-2xl md:text-6xl font-black text-emerald-900 dark:text-emerald-100">
                                    <span class="text-lg opacity-60">Rp</span> {{ number_format($this->batchReportData['finance']['paid'] / 1000000, 1) }} <span class="text-sm md:text-lg">Jt</span>
                                </h3>
                            </div>

                            <div class="bg-rose-50 dark:bg-rose-500/10 p-6 rounded-[2.5rem] border border-rose-100 dark:border-rose-500/20 flex flex-col justify-center relative overflow-hidden">
                                <div class="flex items-center gap-2 mb-2 relative z-10">
                                    <x-heroicon-s-exclamation-circle class="w-5 h-5 text-rose-500" />
                                    <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest">Sisa Tagihan</p>
                                </div>
                                <h3 class="text-2xl md:text-6xl font-black text-rose-900 dark:text-rose-100 relative z-10">
                                    <span class="text-lg opacity-60">Rp</span> {{ number_format($this->batchReportData['finance']['arrears'] / 1000000, 1) }} <span class="text-sm md:text-lg">Jt</span>
                                </h3>
                                <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-rose-500/10 rounded-full blur-xl"></div>
                            </div>

                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                        <div class="lg:col-span-5 bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 flex flex-col h-full">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                                        <x-heroicon-s-ticket class="w-5 h-5 text-blue-500" /> Penerbangan
                                    </h3>
                                </div>
                                <button wire:click="exportFlightPdf" class="group flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-500/10 rounded-lg text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wider hover:bg-blue-100 transition">
                                    <x-heroicon-s-arrow-down-tray class="w-3 h-3 group-hover:animate-bounce" /> PDF
                                </button>
                            </div>
                            
                            <div class="space-y-4 flex-1 overflow-y-auto custom-scrollbar pr-2 max-h-[300px]">
                                @forelse($this->batchReportData['flights'] as $flight)
                                <div class="flex gap-4 relative group">
                                    <div class="absolute left-[19px] top-8 bottom-0 w-0.5 bg-slate-100 dark:bg-white/5 group-last:hidden"></div>
                                    
                                    <div class="w-10 flex flex-col items-center gap-1 shrink-0 pt-1">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 font-black text-xs shadow-sm border border-blue-100 dark:border-blue-500/20">
                                            {{ Carbon::parse($flight->depart_at)->format('H:i') }}
                                        </div>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase">{{ Carbon::parse($flight->depart_at)->format('d M') }}</span>
                                    </div>
                                    
                                    <div class="flex-1 bg-slate-50 dark:bg-white/5 p-3 rounded-xl border border-slate-100 dark:border-white/5">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-black text-slate-800 dark:text-white text-sm">{{ $flight->airline }}</p>
                                                <p class="text-[10px] font-bold text-slate-400 tracking-wider">{{ $flight->flight_number }}</p>
                                            </div>
                                            <x-heroicon-o-paper-airplane class="w-4 h-4 text-slate-300 -rotate-45" />
                                        </div>
                                        <div class="mt-2 flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-zinc-300">
                                            <span>{{ $flight->depart_airport }}</span>
                                            <span class="text-blue-400">→</span>
                                            <span>{{ $flight->arrival_airport }}</span>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-8 opacity-40">
                                    <x-heroicon-o-ticket class="w-12 h-12 mx-auto mb-2 text-slate-300" />
                                    <p class="text-xs font-bold uppercase tracking-widest">Belum ada jadwal</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="lg:col-span-4 bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 flex flex-col h-full">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                                    <x-heroicon-s-building-office-2 class="w-5 h-5 text-purple-500" /> Akomodasi
                                </h3>
                                <button wire:click="exportRoomingPdf" class="group flex items-center gap-2 px-3 py-1.5 bg-purple-50 dark:bg-purple-500/10 rounded-lg text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-wider hover:bg-purple-100 transition">
                                    <x-heroicon-s-arrow-down-tray class="w-3 h-3 group-hover:animate-bounce" /> Rooming
                                </button>
                            </div>

                            @if(isset($this->batchReportData['hotels']) && $this->batchReportData['hotels']->count() > 0)
                                <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                                    @foreach($this->batchReportData['hotels'] as $hotel)
                                    <div class="group p-3 rounded-xl border border-slate-100 dark:border-white/5 hover:border-purple-200 transition-colors bg-white dark:bg-zinc-900 shadow-sm">
                                        <div class="flex gap-3">
                                            <div class="w-12 text-center shrink-0 flex flex-col justify-center bg-purple-50 dark:bg-purple-500/10 rounded-lg">
                                                <span class="block text-lg font-black text-purple-700 dark:text-purple-400 leading-none">
                                                    {{ Carbon::parse($hotel->check_in)->format('d') }}
                                                </span>
                                                <span class="text-[9px] font-bold text-purple-400 uppercase">
                                                    {{ Carbon::parse($hotel->check_in)->format('M') }}
                                                </span>
                                            </div>

                                            <div class="flex-1">
                                                <h4 class="font-bold text-slate-900 dark:text-white text-sm line-clamp-1">{{ $hotel->hotel_name }}</h4>
                                                <div class="flex items-center gap-1 mt-1">
                                                    <x-heroicon-s-map-pin class="w-3 h-3 text-slate-400" />
                                                    <span class="text-[10px] font-bold text-slate-500">{{ $hotel->city }}</span>
                                                    <span class="text-[8px] px-1.5 py-0.5 bg-slate-100 dark:bg-white/10 rounded text-slate-500 ml-auto font-bold">
                                                        {{ Carbon::parse($hotel->check_in)->diffInDays(\Carbon\Carbon::parse($hotel->check_out)) }} Malam
                                                    </span>
                                                </div>
                                                @if(isset($hotel->star))
                                                <div class="flex text-yellow-400 text-[8px] mt-1">
                                                    @for($i = 0; $i < $hotel->star; $i++) <x-heroicon-s-star class="w-2.5 h-2.5" /> @endfor
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex-1 flex flex-col items-center justify-center text-center py-6 opacity-40 border-2 border-dashed border-slate-100 dark:border-white/5 rounded-xl">
                                    <x-heroicon-o-building-office class="w-10 h-10 mb-2 text-slate-300" />
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Belum ada hotel</p>
                                </div>
                            @endif
                        </div>

                        <div class="lg:col-span-3 bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 h-full">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                                    <x-heroicon-s-document-text class="w-5 h-5 text-orange-500" /> Dokumen
                                </h3>
                                <button wire:click="exportPdf" class="flex items-center gap-1 text-[10px] font-bold text-orange-600 bg-orange-50 dark:bg-orange-500/10 px-2 py-1 rounded hover:bg-orange-100 transition">
                                    <x-heroicon-s-arrow-down-tray class="w-3 h-3" /> PDF
                                </button>
                            </div>

                            <div class="space-y-6">
                                @php $pax = $this->batchReportData['stats']['pax_count'] > 0 ? $this->batchReportData['stats']['pax_count'] : 1; @endphp
                                
                                <div>
                                    <div class="flex justify-between text-xs font-bold mb-1">
                                        <span class="text-slate-500 dark:text-zinc-400">Paspor</span>
                                        <span class="text-slate-900 dark:text-white">{{ $this->batchReportData['stats']['passport'] }} / {{ $pax }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                        <div class="bg-blue-500 h-full rounded-full transition-all duration-1000" style="width: {{ ($this->batchReportData['stats']['passport'] / $pax) * 100 }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between text-xs font-bold mb-1">
                                        <span class="text-slate-500 dark:text-zinc-400">Visa Issued</span>
                                        <span class="text-slate-900 dark:text-white">{{ $this->batchReportData['stats']['visa'] }} / {{ $pax }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-1000" style="width: {{ ($this->batchReportData['stats']['visa'] / $pax) * 100 }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between text-xs font-bold mb-1">
                                        <span class="text-slate-500 dark:text-zinc-400">Logistik</span>
                                        <span class="text-slate-900 dark:text-white">{{ $this->batchReportData['stats']['logistics'] }} / {{ $pax }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                        <div class="bg-orange-500 h-full rounded-full transition-all duration-1000" style="width: {{ ($this->batchReportData['stats']['logistics'] / $pax) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] border border-slate-100 dark:border-white/5 flex flex-col h-full overflow-hidden shadow-sm">
                            <div class="p-5 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-zinc-800/30">
                                <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight text-sm">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-sm shadow-blue-500/50"></span>
                                    Persiapan (Pre)
                                </h3>
                            </div>
                            <div class="p-6 space-y-6 flex-1 overflow-y-auto max-h-[400px] custom-scrollbar">
                                @forelse($this->batchReportData['rundown']['pre'] as $rd)
                                <div class="relative pl-6 border-l-2 border-blue-100 dark:border-blue-900/50 group">
                                    <div class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-zinc-900 bg-blue-400 group-hover:scale-125 transition"></div>
                                    <p class="text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">
                                        {{ Carbon::parse($rd->date)->format('d M Y') }}
                                    </p>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-tight mb-1">{{ $rd->activity }}</h4>
                                    <div class="flex items-center gap-1 text-[10px] text-slate-500">
                                        <x-heroicon-m-clock class="w-3 h-3" /> {{ Carbon::parse($rd->time_start)->format('H:i') }}
                                        <span class="mx-1">•</span> 
                                        {{ $rd->location }}
                                    </div>
                                </div>
                                @empty
                                <p class="text-xs text-slate-400 italic text-center py-4">Tidak ada kegiatan pre-departure.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] border-2 border-purple-100 dark:border-purple-900/30 shadow-xl flex flex-col h-full relative overflow-hidden transform md:-translate-y-2 z-10">
                            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                            <div class="p-5 border-b border-slate-100 dark:border-white/5 bg-purple-50/50 dark:bg-purple-900/10">
                                <h3 class="font-black text-purple-900 dark:text-white flex items-center gap-2 uppercase tracking-tight text-sm">
                                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-sm shadow-purple-500/50"></span>
                                    Saat Umrah (During)
                                </h3>
                            </div>
                            <div class="p-6 space-y-6 flex-1 overflow-y-auto max-h-[420px] custom-scrollbar">
                                @forelse($this->batchReportData['rundown']['during'] as $rd)
                                <div class="relative pl-6 border-l-2 border-purple-200 dark:border-purple-800/50 group">
                                    <div class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-zinc-900 bg-purple-500 group-hover:scale-125 transition"></div>
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 text-[9px] font-black mb-1.5 uppercase tracking-wider">
                                        HARI KE-{{ $rd->day_number }}
                                    </span>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-tight mb-1">{{ $rd->activity }}</h4>
                                    <div class="flex items-center gap-1 text-[10px] text-slate-500">
                                        <x-heroicon-m-clock class="w-3 h-3" /> {{ Carbon::parse($rd->time_start)->format('H:i') }}
                                        <span class="mx-1">•</span> 
                                        {{ $rd->location }}
                                    </div>
                                    @if($rd->description)
                                        <p class="text-[10px] text-slate-500 mt-2 bg-slate-50 dark:bg-zinc-800 p-2 rounded-lg italic leading-relaxed">"{{ Str::limit($rd->description, 60) }}"</p>
                                    @endif
                                </div>
                                @empty
                                <p class="text-xs text-slate-400 italic text-center py-4">Belum ada rundown ibadah.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] border border-slate-100 dark:border-white/5 flex flex-col h-full overflow-hidden shadow-sm">
                            <div class="p-5 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-zinc-800/30">
                                <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight text-sm">
                                    <span class="w-2.5 h-2.5 rounded-full bg-orange-500 shadow-sm shadow-orange-500/50"></span>
                                    Pasca Umrah (Post)
                                </h3>
                            </div>
                            <div class="p-6 space-y-6 flex-1 overflow-y-auto max-h-[400px] custom-scrollbar">
                                @forelse($this->batchReportData['rundown']['post'] as $rd)
                                <div class="relative pl-6 border-l-2 border-orange-100 dark:border-orange-900/50 group">
                                    <div class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-zinc-900 bg-orange-400 group-hover:scale-125 transition"></div>
                                    <p class="text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">
                                        {{ Carbon::parse($rd->date)->format('d M Y') }}
                                    </p>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-tight mb-1">{{ $rd->activity }}</h4>
                                    <div class="flex items-center gap-1 text-[10px] text-slate-500">
                                        <x-heroicon-m-clock class="w-3 h-3" /> {{ Carbon::parse($rd->time_start)->format('H:i') }}
                                        <span class="mx-1">•</span> 
                                        {{ $rd->location }}
                                    </div>
                                </div>
                                @empty
                                <p class="text-xs text-slate-400 italic text-center py-4">Tidak ada kegiatan pasca umrah.</p>
                                @endforelse
                            </div>
                        </div>

                    </div>

                    @else
                    <div class="flex flex-col items-center justify-center py-24 bg-white dark:bg-zinc-900 rounded-[3rem] border-2 border-dashed border-slate-200 dark:border-white/10 group">
                        <div class="p-6 bg-slate-50 dark:bg-zinc-800 rounded-full mb-6 group-hover:scale-110 transition-transform duration-500">
                            <x-heroicon-o-cube class="w-16 h-16 text-slate-300 dark:text-zinc-600" />
                        </div>
                        <h3 class="text-xl font-black text-slate-700 dark:text-white mb-2 uppercase tracking-tight">Pilih Batch Keberangkatan</h3>
                        <p class="text-sm text-slate-400 dark:text-zinc-500 max-w-sm text-center">Silakan pilih paket umrah pada filter di atas untuk melihat analisa lengkap mengenai seat, keuangan, dan operasional.</p>
                    </div>
                    @endif

                </div>
                @endif

                @if($activeTab === 'finance')
                <div class="animate-fade-in space-y-8">

                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-lg border border-slate-100 dark:border-white/5 relative overflow-hidden">
                        <div class="relative z-10">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-500/10 rounded-xl text-emerald-600 dark:text-emerald-400">
                                    <x-heroicon-s-banknotes class="w-6 h-6" />
                                </div>
                                Financial Report
                            </h2>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-2 ml-14 uppercase tracking-widest">
                                Laporan Keuangan & Arus Kas
                            </p>
                        </div>

                        <div class="mt-4 md:mt-0 relative z-10 flex items-center gap-2 bg-slate-50 dark:bg-zinc-800 p-1.5 rounded-xl border border-slate-200 dark:border-white/10">
                            <input type="date" wire:model.live="dateStart" 
                                class="bg-transparent border-none text-xs font-bold text-slate-600 dark:text-zinc-300 focus:ring-0 p-2 cursor-pointer uppercase tracking-wider">
                            <span class="text-slate-400 font-black">-</span>
                            <input type="date" wire:model.live="dateEnd" 
                                class="bg-transparent border-none text-xs font-bold text-slate-600 dark:text-zinc-300 focus:ring-0 p-2 cursor-pointer uppercase tracking-wider">
                        </div>

                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-emerald-500/5 to-teal-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div class="bg-emerald-500 dark:bg-emerald-600 p-6 rounded-[2.5rem] shadow-lg shadow-emerald-500/20 relative overflow-hidden group">
                            <div class="relative z-10">
                                <div class="flex items-center gap-2 mb-2 opacity-80">
                                    <x-heroicon-s-arrow-trending-up class="w-5 h-5 text-white" />
                                    <p class="text-xs font-black text-white uppercase tracking-widest">Total Pemasukan</p>
                                </div>
                                <h2 class="text-3xl md:text-4xl font-black text-white tracking-tight">
                                    <span class="text-lg opacity-60 mr-1">Rp</span>{{ number_format($this->financeStats['income_month'] / 1000000, 1) }}<span class="text-lg opacity-60 ml-1">Jt</span>
                                </h2>
                                <p class="text-[10px] text-emerald-100 font-bold mt-2 opacity-80">Verified Income (Bulan Ini)</p>
                            </div>
                            <x-heroicon-o-banknotes class="absolute -right-6 -bottom-6 w-32 h-32 text-white/20 group-hover:scale-110 transition duration-500 rotate-12" />
                        </div>

                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 relative group hover:border-red-200 transition-colors">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-2 bg-red-50 dark:bg-red-500/10 rounded-lg text-red-500">
                                    <x-heroicon-s-arrow-trending-down class="w-5 h-5" />
                                </div>
                                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Pengeluaran</p>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-black text-red-500 tracking-tight">
                                <span class="text-lg opacity-60 mr-1 text-slate-400">Rp</span>{{ number_format($this->financeStats['expense_month'] / 1000000, 1) }}<span class="text-lg opacity-60 ml-1 text-slate-400">Jt</span>
                            </h2>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 relative group hover:border-indigo-200 transition-colors">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-2 bg-indigo-50 dark:bg-indigo-500/10 rounded-lg text-indigo-500">
                                    <x-heroicon-s-scale class="w-5 h-5" />
                                </div>
                                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Gross Profit</p>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                                <span class="text-lg opacity-60 mr-1 text-slate-400">Rp</span>{{ number_format(($this->financeStats['income_month'] - $this->financeStats['expense_month']) / 1000000, 1) }}<span class="text-lg opacity-60 ml-1 text-slate-400">Jt</span>
                            </h2>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-white/5 shadow-sm">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                <x-heroicon-s-wallet class="w-5 h-5" />
                            </div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Posisi Saldo (Live)</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            @foreach($this->financeStats['wallets'] as $wallet)
                                <div class="p-5 rounded-2xl border transition-all duration-300 hover:-translate-y-1 hover:shadow-lg
                                    {{ $wallet->type == 'bank' 
                                        ? 'bg-blue-50/50 border-blue-100 dark:bg-blue-900/10 dark:border-blue-800/30' 
                                        : 'bg-amber-50/50 border-amber-100 dark:bg-amber-900/10 dark:border-amber-800/30' }}">
                                    
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center gap-2">
                                            @if($wallet->type == 'bank') 
                                                <x-heroicon-s-building-library class="w-4 h-4 text-blue-400" />
                                            @else 
                                                <x-heroicon-s-banknotes class="w-4 h-4 text-amber-500" /> 
                                            @endif
                                            <p class="text-[10px] font-black text-slate-500 dark:text-zinc-400 uppercase tracking-widest truncate max-w-[100px]" title="{{ $wallet->name }}">
                                                {{ \Illuminate\Support\Str::limit($wallet->name, 15) }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <p class="text-xl font-black text-slate-900 dark:text-white">
                                        <span class="text-xs text-slate-400 mr-0.5">Rp</span>{{ number_format($wallet->balance, 0, ',', '.') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] border border-slate-100 dark:border-white/5 shadow-sm overflow-hidden">
                        
                        <div class="p-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-zinc-800/20 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-500">
                                    <x-heroicon-s-calendar-days class="w-5 h-5" />
                                </div>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Rekapan Harian</h3>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 dark:bg-zinc-950/50 text-[10px] text-slate-400 uppercase font-black tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4 pl-8">Tanggal</th>
                                        <th class="px-6 py-4">Pemasukan</th>
                                        <th class="px-6 py-4">Pengeluaran</th>
                                        <th class="px-6 py-4">Saldo Petty Cash</th>
                                        <th class="px-6 py-4 text-center">Export</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                    @forelse($this->dailyFinanceRecap as $day)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition group">
                                        <td class="px-6 py-4 pl-8">
                                            <div class="flex items-center gap-4">
                                                <div class="flex flex-col items-center justify-center bg-slate-100 dark:bg-zinc-800 w-12 h-12 rounded-xl text-slate-500 dark:text-zinc-400 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/20 group-hover:text-indigo-600 transition-colors">
                                                    <span class="text-lg font-black leading-none">{{ $day['date_obj']->format('d') }}</span>
                                                    <span class="text-[9px] font-bold uppercase">{{ $day['date_obj']->format('M') }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-bold text-slate-900 dark:text-white block text-sm">
                                                        {{ $day['date_obj']->translatedFormat('l') }}
                                                    </span>
                                                    @if($day['date_obj']->isToday())
                                                        <span class="inline-block mt-1 text-[9px] text-emerald-700 dark:text-emerald-400 font-black bg-emerald-100 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full uppercase tracking-wide">Hari Ini</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                                <span class="font-black text-emerald-600 dark:text-emerald-400 text-base">
                                                    + {{ number_format($day['income'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                                                <span class="font-black text-red-500 text-base">
                                                    - {{ number_format($day['expense'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="font-bold font-mono text-slate-600 dark:text-zinc-300 bg-slate-100 dark:bg-zinc-800 px-3 py-1 rounded-lg">
                                                Rp {{ number_format($day['petty_cash_balance'], 0, ',', '.') }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <button wire:click="downloadDailyReport('{{ $day['date_str'] }}')" 
                                                class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-400 transition-all" 
                                                title="Download PDF">
                                                <x-heroicon-s-document-arrow-down class="w-5 h-5" />
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="opacity-50 flex flex-col items-center justify-center">
                                                <x-heroicon-o-calendar class="w-12 h-12 text-slate-300 mb-2" />
                                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tidak ada data transaksi</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                @endif

                @if($activeTab === 'marketing')
                <div class="animate-fade-in space-y-8">
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-lg border border-slate-100 dark:border-white/5 relative overflow-hidden">
                        <div class="relative z-10">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                                <div class="p-2 bg-blue-50 dark:bg-blue-500/10 rounded-xl text-blue-600 dark:text-blue-400">
                                    <x-heroicon-s-presentation-chart-line class="w-6 h-6" />
                                </div>
                                Marketing & Sales
                            </h2>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-2 ml-14 uppercase tracking-widest">
                                Performa Tim & Kualitas Leads
                            </p>
                        </div>
                        
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        
                        <div wire:click="showLeadsDetail('personal')" 
                            class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 cursor-pointer hover:border-blue-200 dark:hover:border-blue-500/30 transition group relative overflow-hidden">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2.5 bg-blue-50 dark:bg-blue-500/10 rounded-xl text-blue-600 dark:text-blue-400 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <x-heroicon-s-user class="w-5 h-5" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-blue-500 transition-colors">Personal Leads</span>
                            </div>
                            <h2 class="text-4xl font-black text-slate-900 dark:text-white group-hover:scale-105 transition-transform origin-left">
                                {{ $this->marketingStats['leads_personal'] }}
                            </h2>
                            <div class="absolute -right-4 -bottom-4 opacity-0 group-hover:opacity-100 transition-all duration-500 transform group-hover:scale-110">
                                <x-heroicon-o-magnifying-glass-plus class="w-24 h-24 text-blue-50 dark:text-blue-900/20" />
                            </div>
                        </div>

                        <div wire:click="showLeadsDetail('corporate')" 
                            class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 cursor-pointer hover:border-purple-200 dark:hover:border-purple-500/30 transition group relative overflow-hidden">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2.5 bg-purple-50 dark:bg-purple-500/10 rounded-xl text-purple-600 dark:text-purple-400 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                    <x-heroicon-s-building-office class="w-5 h-5" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-purple-500 transition-colors">Corporate Leads</span>
                            </div>
                            <h2 class="text-4xl font-black text-slate-900 dark:text-white group-hover:scale-105 transition-transform origin-left">
                                {{ $this->marketingStats['leads_corporate'] }}
                            </h2>
                            <div class="absolute -right-4 -bottom-4 opacity-0 group-hover:opacity-100 transition-all duration-500 transform group-hover:scale-110">
                                <x-heroicon-o-building-library class="w-24 h-24 text-purple-50 dark:text-purple-900/20" />
                            </div>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 relative group hover:border-amber-200 transition-colors">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2.5 bg-amber-50 dark:bg-amber-500/10 rounded-xl text-amber-500">
                                    <x-heroicon-s-funnel class="w-5 h-5" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Conversion Rate</span>
                            </div>
                            <h2 class="text-4xl font-black text-slate-900 dark:text-white">
                                {{ number_format($this->marketingStats['conversion_rate'], 1) }}<span class="text-xl text-slate-400">%</span>
                            </h2>
                            <p class="text-[10px] font-bold text-slate-400 mt-2 bg-slate-50 dark:bg-zinc-800 px-2 py-1 rounded-lg inline-block">Leads to Closing Ratio</p>
                        </div>

                        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white p-6 rounded-[2.5rem] shadow-xl shadow-blue-500/20 relative overflow-hidden group">
                            <div class="relative z-10">
                                <div class="flex items-center gap-2 mb-4 opacity-80">
                                    <x-heroicon-s-trophy class="w-5 h-5 text-yellow-300" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">Achievement</span>
                                </div>
                                
                                <div class="flex items-baseline gap-2">
                                    <h2 class="text-4xl font-black">{{ $this->marketingStats['total_closing'] }}</h2>
                                    <span class="text-lg font-bold opacity-60">/ {{ $this->marketingStats['global_target'] }} Pax</span>
                                </div>

                                @php
                                    $globalPercent = $this->marketingStats['global_target'] > 0
                                        ? ($this->marketingStats['total_closing'] / $this->marketingStats['global_target']) * 100
                                        : 0;
                                @endphp
                                
                                <div class="w-full bg-black/20 h-2 rounded-full mt-4 overflow-hidden backdrop-blur-sm">
                                    <div class="bg-white h-full rounded-full transition-all duration-1000" style="width: {{ min($globalPercent, 100) }}%"></div>
                                </div>
                                <p class="text-[10px] font-bold mt-2 text-right opacity-90">{{ number_format($globalPercent, 0) }}% Target Tercapai</p>
                            </div>
                            
                            <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50/50 dark:bg-zinc-800/20">
                            <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                                <x-heroicon-s-user-group class="w-5 h-5 text-indigo-500" />
                                Sales Team Performance
                            </h3>
                            <span class="text-[10px] font-black bg-white dark:bg-zinc-800 border border-slate-200 dark:border-white/10 px-3 py-1 rounded-full text-slate-500 uppercase tracking-widest">
                                Bulan Ini
                            </span>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 dark:bg-zinc-950/50 text-[10px] text-slate-400 uppercase font-black tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4 pl-8">Nama Sales</th>
                                        <th class="px-6 py-4 text-center">Total Leads</th>
                                        <th class="px-6 py-4 text-center">Closing</th>
                                        <th class="px-6 py-4 text-center">Target</th>
                                        <th class="px-6 py-4 text-center">Achievement</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                    @foreach($this->marketingStats['sales_team'] as $sales)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition group">
                                        <td class="px-6 py-4 pl-8">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-zinc-800 flex items-center justify-center text-xs font-black text-slate-500">
                                                    {{ substr($sales->full_name, 0, 1) }}
                                                </div>
                                                <span class="font-bold text-slate-800 dark:text-white">{{ $sales->full_name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-block bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-2.5 py-1 rounded-lg text-xs font-bold">
                                                {{ $sales->total_leads_count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center font-black text-slate-800 dark:text-white text-base">
                                            {{ $sales->closing_count }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-slate-400 font-bold text-xs">
                                            {{ $sales->current_target }} Pax
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-3">
                                                <div class="w-24 bg-slate-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all duration-500 {{ $sales->is_achieved ? 'bg-emerald-500' : 'bg-red-500' }}" 
                                                        style="width: {{ min($sales->achievement_percent, 100) }}%"></div>
                                                </div>
                                                <span class="text-xs font-black {{ $sales->is_achieved ? 'text-emerald-600' : 'text-red-500' }}">
                                                    {{ number_format($sales->achievement_percent, 0) }}%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($sales->is_achieved)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 uppercase tracking-wide">
                                                    <x-heroicon-s-check-badge class="w-3 h-3" /> Achieve
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-red-100 text-red-700 uppercase tracking-wide">
                                                    <x-heroicon-s-exclamation-circle class="w-3 h-3" /> Under
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 p-8 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-6">
                                <x-heroicon-s-star class="w-6 h-6 text-yellow-400" />
                                <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-tight">Top 5 Agen Bulan Ini</h3>
                            </div>
                            
                            <div class="space-y-3 flex-1 overflow-y-auto custom-scrollbar max-h-[350px] pr-2">
                                @forelse($this->marketingStats['top_agents'] as $idx => $agent)
                                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-zinc-800/50 rounded-2xl border border-slate-100 dark:border-white/5 transition hover:border-yellow-200 dark:hover:border-yellow-500/20">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 {{ $idx == 0 ? 'bg-yellow-400 text-white shadow-lg shadow-yellow-400/30' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-500' }} rounded-xl flex items-center justify-center font-black text-sm">
                                            #{{ $idx + 1 }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 dark:text-white">{{ $agent->name }}</p>
                                            <div class="flex items-center gap-1 text-[10px] text-slate-500 font-bold uppercase tracking-wide">
                                                <x-heroicon-s-map-pin class="w-3 h-3 text-slate-400" />
                                                {{ $agent->city ?? 'Kota -' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="block font-black text-xl text-slate-800 dark:text-white">{{ $agent->bookings_count }}</span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Jamaah</span>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-10 opacity-40">
                                    <x-heroicon-o-users class="w-12 h-12 mx-auto mb-2 text-slate-300" />
                                    <p class="text-xs font-bold uppercase tracking-widest">Belum ada performa</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border-2 border-red-100 dark:border-red-900/20 p-8 relative overflow-hidden flex flex-col h-full">
                            <div class="absolute top-0 right-0 p-6 opacity-5 pointer-events-none">
                                <x-heroicon-s-bell-alert class="w-40 h-40 text-red-600" />
                            </div>
                            
                            <div class="relative z-10 mb-6">
                                <div class="flex items-center gap-2 text-red-600">
                                    <x-heroicon-s-exclamation-triangle class="w-6 h-6" />
                                    <h3 class="font-black uppercase tracking-tight">Perlu Follow Up!</h3>
                                </div>
                                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Agen tanpa jamaah > 3 bulan</p>
                            </div>
                            
                            <div class="space-y-2 flex-1 overflow-y-auto custom-scrollbar max-h-[350px] pr-2 relative z-10">
                                @forelse($this->marketingStats['dormant_agents'] as $agent)
                                <div class="flex items-center justify-between p-3 bg-red-50/50 dark:bg-red-900/10 rounded-xl border border-transparent hover:border-red-200 transition group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-zinc-300">{{ $agent->name }}</span>
                                    </div>
                                    <a href="#" class="px-3 py-1.5 bg-white dark:bg-zinc-800 text-[10px] font-black text-red-600 uppercase tracking-wider rounded-lg shadow-sm group-hover:bg-red-600 group-hover:text-white transition-colors">
                                        Hubungi
                                    </a>
                                </div>
                                @empty
                                <div class="flex flex-col items-center justify-center h-full py-10 text-emerald-600">
                                    <x-heroicon-s-check-circle class="w-16 h-16 mb-2 opacity-20" />
                                    <p class="font-black uppercase tracking-widest text-sm">Semua Agen Aktif!</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </div>
                @endif

                @if($activeTab === 'media')
                <div class="animate-fade-in space-y-8">
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-lg border border-slate-100 dark:border-white/5 relative overflow-hidden">
                        <div class="relative z-10">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                                <div class="p-2 bg-pink-50 dark:bg-pink-500/10 rounded-xl text-pink-600 dark:text-pink-400">
                                    <x-heroicon-s-swatch class="w-6 h-6" />
                                </div>
                                Media & Content
                            </h2>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-2 ml-14 uppercase tracking-widest">
                                Creative Studio & Publishing
                            </p>
                        </div>
                        
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-pink-500/5 to-rose-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 relative overflow-hidden group hover:border-yellow-200 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <div class="p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl text-yellow-600">
                                    <x-heroicon-s-paint-brush class="w-6 h-6" />
                                </div>
                                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg text-[10px] font-black uppercase tracking-widest">Pending</span>
                            </div>
                            <h2 class="text-4xl font-black text-slate-900 dark:text-white mb-1">{{ $this->mediaStats['requests_pending'] }}</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Antrian Request Desain</p>
                            
                            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:opacity-20 transition-opacity transform group-hover:scale-110">
                                <x-heroicon-s-clock class="w-24 h-24 text-yellow-500" />
                            </div>
                        </div>

                        <div wire:click="showMediaDetail('published')" 
                            class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 relative overflow-hidden cursor-pointer hover:border-green-200 hover:shadow-md transition group">
                            <div class="flex justify-between items-start mb-4">
                                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-xl text-green-600">
                                    <x-heroicon-s-check-badge class="w-6 h-6" />
                                </div>
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-[10px] font-black uppercase tracking-widest">Published</span>
                            </div>
                            <h2 class="text-4xl font-black text-slate-900 dark:text-white mb-1">{{ $this->mediaStats['published_month'] }}</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Konten Tayang Bulan Ini</p>
                            
                            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:opacity-20 transition-opacity transform group-hover:scale-110">
                                <x-heroicon-s-share class="w-24 h-24 text-green-500" />
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-indigo-600 to-violet-700 p-6 rounded-[2.5rem] shadow-xl text-white flex flex-col justify-center items-center text-center relative overflow-hidden group">
                            <div class="relative z-10">
                                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-3 backdrop-blur-sm">
                                    <x-heroicon-s-folder-open class="w-6 h-6 text-white" />
                                </div>
                                <h3 class="font-black text-xl mb-1">Creative Assets</h3>
                                <p class="text-xs font-medium text-indigo-200 mb-6">Akses Bank Foto, Video & Dokumen</p>
                                
                                <button wire:click="showMediaDetail('assets')" 
                                    class="bg-white text-indigo-700 px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-50 transition shadow-lg active:scale-95">
                                    Buka Penyimpanan
                                </button>
                            </div>
                            
                            <x-heroicon-s-photo class="w-24 h-24 absolute -left-4 -bottom-4 text-white/5 group-hover:scale-110 transition-transform duration-700 rotate-12" />
                            <x-heroicon-s-video-camera class="w-24 h-24 absolute -right-4 -top-4 text-white/5 group-hover:scale-110 transition-transform duration-700 -rotate-12" />
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] border border-slate-100 dark:border-white/5 shadow-sm overflow-hidden">
                    
                        <div class="p-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-zinc-800/20 flex justify-between items-center">
                            <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                                <x-heroicon-s-calendar-days class="w-5 h-5 text-pink-500" />
                                Rundown Konten Bulan Ini
                            </h3>
                        </div>

                        <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto custom-scrollbar">
                            
                            @forelse($this->mediaStats['content_schedule'] as $schedule)
                            <div class="flex gap-4 group">
                                
                                <div class="w-16 flex flex-col items-center pt-2 shrink-0">
                                    <span class="text-2xl font-black text-slate-800 dark:text-white leading-none">
                                        {{ $schedule->scheduled_date->format('d') }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        {{ $schedule->scheduled_date->format('M') }}
                                    </span>
                                    <span class="mt-1 px-2 py-0.5 bg-slate-100 dark:bg-zinc-800 rounded text-[9px] font-bold text-slate-500">
                                        {{ $schedule->scheduled_date->format('D') }}
                                    </span>
                                </div>

                                <div class="flex-1 bg-white dark:bg-zinc-950 p-5 rounded-2xl border border-slate-100 dark:border-zinc-800 shadow-sm hover:shadow-md hover:border-pink-200 dark:hover:border-pink-900/30 transition relative overflow-hidden group/card">
                                    
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-slate-900 dark:text-white text-base leading-snug pr-20">
                                            {{ $schedule->title }}
                                        </h4>
                                        
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] uppercase font-black tracking-wide
                                            {{ match ($schedule->status) {
                                                'published' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                                'ready' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                'draft' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                default => 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-zinc-400'
                                            } }}">
                                            {{ $schedule->status }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-slate-500 dark:text-zinc-400 line-clamp-2 mb-4">
                                        {{ $schedule->caption_draft ?? 'Belum ada caption...' }}
                                    </p>

                                    <div class="flex flex-wrap gap-2 pt-4 border-t border-slate-50 dark:border-white/5">
                                        @php
                                            $links = [];
                                            if ($schedule->status === 'published' && !empty($schedule->attachment_path)) {
                                                $links = json_decode($schedule->attachment_path, true) ?? [];
                                            }
                                            $platforms = $schedule->platforms ?? []; 
                                        @endphp

                                        @foreach($platforms as $plat)
                                            @php
                                                $url = $links[$plat] ?? null;
                                                $isClickable = $schedule->status === 'published' && !empty($url);

                                                $style = match ($plat) {
                                                    'instagram' => 'bg-pink-50 text-pink-600 border-pink-100 hover:border-pink-300 dark:bg-pink-900/10 dark:text-pink-400 dark:border-pink-800',
                                                    'tiktok' => 'bg-gray-100 text-gray-800 border-gray-200 hover:border-gray-400 dark:bg-white/10 dark:text-white dark:border-white/20',
                                                    'facebook' => 'bg-blue-50 text-blue-600 border-blue-100 hover:border-blue-300 dark:bg-blue-900/10 dark:text-blue-400 dark:border-blue-800',
                                                    'youtube' => 'bg-red-50 text-red-600 border-red-100 hover:border-red-300 dark:bg-red-900/10 dark:text-red-400 dark:border-red-800',
                                                    default => 'bg-gray-50 text-gray-600 border-gray-200'
                                                };
                                            @endphp

                                            @if($isClickable)
                                                <a href="{{ $url }}" target="_blank" 
                                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-[10px] font-bold uppercase tracking-wider transition {{ $style }}">
                                                    @if($plat == 'instagram') <span>📸 IG</span>
                                                    @elseif($plat == 'tiktok') <span>🎵 TT</span>
                                                    @elseif($plat == 'facebook') <span>📘 FB</span>
                                                    @elseif($plat == 'youtube') <span>▶️ YT</span>
                                                    @else {{ ucfirst($plat) }} @endif
                                                    <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3 opacity-50" />
                                                </a>
                                            @else
                                                <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-dashed text-[10px] font-bold uppercase tracking-wider opacity-60 {{ $style }}">
                                                    @if($plat == 'instagram') <span>📸 IG</span>
                                                    @elseif($plat == 'tiktok') <span>🎵 TT</span>
                                                    @elseif($plat == 'facebook') <span>📘 FB</span>
                                                    @elseif($plat == 'youtube') <span>▶️ YT</span>
                                                    @else {{ ucfirst($plat) }} @endif
                                                </span>
                                            @endif
                                        @endforeach
                                        
                                        @if(empty($platforms))
                                            <span class="text-[10px] text-slate-400 italic mt-1">Belum ada platform dipilih.</span>
                                        @endif
                                    </div>

                                </div>
                            </div>
                            @empty
                            <div class="text-center py-16 flex flex-col items-center opacity-50">
                                <div class="bg-slate-50 dark:bg-zinc-800 p-4 rounded-full mb-3">
                                    <x-heroicon-o-calendar class="w-8 h-8 text-slate-400" />
                                </div>
                                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Belum ada jadwal konten</p>
                            </div>
                            @endforelse

                        </div>
                    </div>

                </div>

                <div x-show="$wire.showMediaListModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
                    
                    <div wire:click="$set('showMediaListModal', false)" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>
                    
                    <div class="relative bg-white dark:bg-zinc-900 w-full max-w-4xl rounded-[2.5rem] shadow-2xl flex flex-col max-h-[85vh] border border-white/10 overflow-hidden" x-transition.move.up>
                        
                        <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                            <h3 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                                @if($mediaListType === 'published')
                                    <div class="p-2 bg-green-50 dark:bg-green-500/10 rounded-xl text-green-600">
                                        <x-heroicon-s-check-badge class="w-5 h-5" />
                                    </div>
                                    History Publish
                                @else
                                    <div class="p-2 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl text-indigo-600">
                                        <x-heroicon-s-photo class="w-5 h-5" />
                                    </div>
                                    Asset Preview
                                @endif
                            </h3>
                            <button wire:click="$set('showMediaListModal', false)" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                                <x-heroicon-s-x-mark class="w-6 h-6" />
                            </button>
                        </div>

                        <div class="overflow-y-auto custom-scrollbar p-6 flex-1 bg-slate-50/50 dark:bg-black/20">
                            
                            @if($mediaListType === 'published')
                                <div class="grid grid-cols-1 gap-3">
                                    @forelse($mediaListData as $item)
                                    <div class="flex items-center justify-between p-4 bg-white dark:bg-zinc-800 rounded-2xl border border-slate-100 dark:border-white/5 hover:border-green-200 transition-colors shadow-sm">
                                        <div class="flex items-center gap-4">
                                            <div class="flex flex-col items-center justify-center w-14 h-14 bg-green-50 dark:bg-green-900/20 rounded-xl text-green-600 font-black text-xs uppercase border border-green-100 dark:border-green-800/30">
                                                <span class="text-lg leading-none">{{ $item->updated_at->format('d') }}</span>
                                                <span class="text-[9px]">{{ $item->updated_at->format('M') }}</span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white">{{ $item->title }}</p>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 dark:bg-white/10 rounded text-slate-500 uppercase">{{ $item->platform }}</span>
                                                    <span class="text-xs text-slate-400">• PIC: {{ $item->pic_name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        @if($item->link)
                                        <a href="{{ $item->link }}" target="_blank" class="px-4 py-2 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-indigo-100 transition">
                                            Lihat <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3"/>
                                        </a>
                                        @endif
                                    </div>
                                    @empty
                                    <div class="text-center py-12 opacity-50">
                                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Belum ada history publish.</p>
                                    </div>
                                    @endforelse
                                </div>

                            @else
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @forelse($mediaListData as $asset)
                                    <div class="group relative aspect-square bg-white dark:bg-zinc-800 rounded-2xl overflow-hidden border border-slate-200 dark:border-white/5 shadow-sm">
                                        <div class="absolute inset-0 flex items-center justify-center text-slate-300 dark:text-zinc-600">
                                            <x-heroicon-s-document class="w-10 h-10" />
                                        </div>
                                        
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-4 opacity-0 group-hover:opacity-100 transition duration-300">
                                            <p class="text-white text-xs font-bold truncate mb-2">{{ $asset->file_name ?? 'File Asset' }}</p>
                                            <a href="#" class="w-full bg-white text-black py-2 rounded-lg text-center text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 transition">
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-span-4 text-center py-12 opacity-50">
                                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Belum ada aset tersimpan.</p>
                                    </div>
                                    @endforelse
                                </div>
                                <div class="mt-8 text-center">
                                    <a href="{{ route('creative') }}" class="inline-flex items-center gap-2 text-xs font-black text-indigo-600 hover:text-indigo-700 uppercase tracking-widest transition">
                                        Buka Creative Studio Full <x-heroicon-m-arrow-right class="w-4 h-4" />
                                    </a>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
                @endif

                @if($activeTab === 'hr')
                <div class="animate-fade-in space-y-8">
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-lg border border-slate-100 dark:border-white/5 relative overflow-hidden">
                        <div class="relative z-10">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                                <div class="p-2 bg-orange-50 dark:bg-orange-500/10 rounded-xl text-orange-600 dark:text-orange-400">
                                    <x-heroicon-s-users class="w-6 h-6" />
                                </div>
                                Human Resources
                            </h2>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-2 ml-14 uppercase tracking-widest">
                                Manajemen Karyawan & KPI
                            </p>
                        </div>
                        
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-orange-500/5 to-amber-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 flex items-center gap-5 hover:border-orange-200 transition-colors group">
                            <div class="p-4 bg-orange-50 dark:bg-orange-500/10 rounded-2xl text-orange-600 group-hover:scale-110 transition-transform">
                                <x-heroicon-s-user-group class="w-8 h-8" />
                            </div>
                            <div>
                                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Total Karyawan</p>
                                <h2 class="text-4xl font-black text-slate-900 dark:text-white">{{ $this->hrStats['total_employees'] }}</h2>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 flex items-center gap-5 hover:border-blue-200 transition-colors group">
                            <div class="p-4 bg-blue-50 dark:bg-blue-500/10 rounded-2xl text-blue-600 group-hover:scale-110 transition-transform">
                                <x-heroicon-s-clipboard-document-list class="w-8 h-8" />
                            </div>
                            <div>
                                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Tugas Hari Ini</p>
                                <h2 class="text-4xl font-black text-slate-900 dark:text-white">{{ $this->hrStats['active_tasks'] }}</h2>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 p-6 rounded-[2.5rem] shadow-lg text-white flex items-center gap-5 relative overflow-hidden group">
                            <div class="relative z-10 p-4 bg-white/20 rounded-2xl backdrop-blur-sm">
                                <x-heroicon-s-chart-bar class="w-8 h-8 text-white" />
                            </div>
                            <div class="relative z-10">
                                <p class="text-xs font-black text-emerald-100 uppercase tracking-widest mb-1">Rata-rata KPI</p>
                                <h2 class="text-4xl font-black">{{ number_format($this->hrStats['avg_performance'], 0) }}%</h2>
                            </div>
                            <x-heroicon-o-chart-pie class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10 group-hover:scale-110 transition-transform duration-700 rotate-12" />
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] border border-slate-100 dark:border-white/5 shadow-sm overflow-hidden">
                        
                        <div class="p-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-zinc-800/20 flex justify-between items-center">
                            <h3 class="font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                                <x-heroicon-s-briefcase class="w-5 h-5 text-orange-500" />
                                Monitoring Produktivitas
                            </h3>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left whitespace-nowrap">
                                <thead class="bg-slate-50 dark:bg-zinc-950/50 text-[10px] text-slate-400 uppercase font-black tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4 pl-8">Nama Karyawan</th>
                                        <th class="px-6 py-4">Departemen</th>
                                        <th class="px-6 py-4 text-center">Progres Hari Ini</th>
                                        <th class="px-6 py-4 text-center">Performa Bulan Ini</th>
                                        <th class="px-6 py-4 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                    @foreach($this->hrStats['employees'] as $emp)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition group">
                                        
                                        <td class="px-6 py-4 pl-8">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 font-black text-xs">
                                                    {{ substr($emp->full_name, 0, 2) }}
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-900 dark:text-white">{{ $emp->full_name }}</p>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $emp->position }}</p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-zinc-300 uppercase tracking-wider">
                                                {{ $emp->departmentRel->name ?? '-' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $dailyText = $emp->daily_total === 0 ? 'No Task' : "{$emp->daily_done}/{$emp->daily_total}";
                                                $badgeColor = 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-zinc-500';
                                                
                                                if ($emp->daily_total > 0) {
                                                    if ($emp->daily_done == $emp->daily_total) {
                                                        $badgeColor = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
                                                    } elseif ($emp->daily_done == 0) {
                                                        $badgeColor = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                                                    } else {
                                                        $badgeColor = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                                                    }
                                                }
                                            @endphp
                                            <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wide {{ $badgeColor }}">
                                                {{ $dailyText }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <div class="flex flex-col items-center gap-1">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-black text-sm {{ $emp->monthly_percent >= 80 ? 'text-emerald-600' : ($emp->monthly_percent >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                                        {{ $emp->monthly_percent }}%
                                                    </span>
                                                    @if($emp->monthly_percent == 100)
                                                        <x-heroicon-s-trophy class="w-3 h-3 text-yellow-400" />
                                                    @endif
                                                </div>
                                                
                                                <div class="w-24 bg-slate-100 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all duration-1000 {{ $emp->monthly_percent >= 80 ? 'bg-emerald-500' : ($emp->monthly_percent >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" 
                                                        style="width: {{ $emp->monthly_percent }}%"></div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <button wire:click="viewEmployeeTasks({{ $emp->id }})" 
                                                class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-50 dark:bg-white/5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all">
                                                <x-heroicon-m-eye class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div x-show="$wire.showHrModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
                    
                    <div wire:click="$set('showHrModal', false)" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

                    <div class="relative bg-white dark:bg-zinc-900 w-full max-w-2xl rounded-[2.5rem] shadow-2xl flex flex-col max-h-[85vh] border border-white/10 overflow-hidden" x-transition.move.up>
                        
                        <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Detail Tugas</h3>
                                <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-widest mt-1">{{ $selectedEmployeeName }}</p>
                            </div>
                            <button wire:click="$set('showHrModal', false)" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                                <x-heroicon-s-x-mark class="w-6 h-6" />
                            </button>
                        </div>

                        <div class="overflow-y-auto custom-scrollbar p-6 space-y-4 bg-slate-50/50 dark:bg-black/20 flex-1">
                            @forelse($employeeTasks as $task)
                            <div class="bg-white dark:bg-zinc-950 p-5 rounded-2xl border border-slate-100 dark:border-white/5 shadow-sm relative overflow-hidden group">
                                
                                <div class="absolute left-0 top-0 bottom-0 w-1 
                                    {{ match ($task->status) {
                                        'completed' => 'bg-green-500',
                                        'pending' => 'bg-red-500',
                                        'in_progress' => 'bg-yellow-500',
                                        default => 'bg-gray-300'
                                    } }}">
                                </div>

                                <div class="pl-3">
                                    <h4 class="font-bold text-slate-900 dark:text-white mb-3 text-base">{{ $task->title }}</h4>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Frekuensi</span>
                                            @php
                                                $freq = $task->template->frequency ?? 'daily';
                                                $freqColor = match ($freq) {
                                                    'daily' => 'text-green-600 bg-green-50 dark:bg-green-900/20',
                                                    'weekly' => 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20',
                                                    'monthly' => 'text-blue-600 bg-blue-50 dark:bg-blue-900/20',
                                                    default => 'text-gray-600 bg-gray-50'
                                                };                                                                                          
                                            @endphp
                                            <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase {{ $freqColor }}">
                                                {{ $freq }}
                                            </span>
                                        </div>

                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Status</span>
                                            @php
                                                $statusColor = match ($task->status) {
                                                    'completed' => 'text-green-600 bg-green-50 dark:bg-green-900/20',
                                                    'pending' => 'text-red-600 bg-red-50 dark:bg-red-900/20',
                                                    'in_progress' => 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20',
                                                    default => 'text-gray-600 bg-gray-50'
                                                };
                                            @endphp
                                            <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase {{ $statusColor }}">
                                                {{ str_replace('_', ' ', $task->status) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-white/5 flex items-center gap-2">
                                        <x-heroicon-m-clock class="w-4 h-4 text-slate-400" />
                                        <span class="text-xs font-bold {{ $task->due_date < now() && $task->status !== 'completed' ? 'text-red-500' : 'text-slate-600 dark:text-zinc-400' }}">
                                            Deadline: {{ Carbon::parse($task->due_date)->format('d M, H:i') }}
                                        </span>
                                    </div>

                                    @if($task->status === 'completed')
                                    <div class="mt-3 bg-green-50 dark:bg-green-900/10 p-3 rounded-xl border border-green-100 dark:border-green-900/30">
                                        <span class="text-[10px] font-black text-green-700 dark:text-green-400 uppercase tracking-widest block mb-1">Catatan Penyelesaian</span>
                                        <p class="text-xs text-green-800 dark:text-green-300 italic">
                                            "{{ $task->completion_note ?? '-' }}"
                                        </p>
                                    </div>
                                    @endif
                                </div>

                            </div>
                            @empty
                            <div class="text-center py-12 opacity-50">
                                <x-heroicon-o-clipboard-document-check class="w-16 h-16 text-slate-300 mx-auto mb-3" />
                                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Tidak ada tugas hari ini</p>
                            </div>
                            @endforelse
                        </div>

                        <div class="p-5 border-t border-slate-100 dark:border-white/5 bg-white dark:bg-zinc-900 flex justify-end">
                            <button wire:click="$set('showHrModal', false)" class="px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-black rounded-xl font-black text-xs uppercase tracking-widest hover:opacity-80 transition shadow-lg">
                                Tutup Panel
                            </button>
                        </div>

                    </div>
                </div>
                @endif
            </div>

            <div x-show="$wire.showLeadsModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
    
                <div wire:click="$set('showLeadsModal', false)" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer transition-opacity"></div>

                <div class="relative bg-white dark:bg-zinc-900 w-full max-w-5xl rounded-[2.5rem] shadow-2xl flex flex-col max-h-[85vh] border border-white/10 overflow-hidden" x-transition.move.up>
                    
                    <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-3 uppercase tracking-tight">
                                @if($leadsDetailType === 'corporate')
                                    <div class="p-2 bg-purple-50 dark:bg-purple-500/10 rounded-xl text-purple-600 dark:text-purple-400">
                                        <x-heroicon-s-building-office class="w-5 h-5" />
                                    </div>
                                    Detail Corporate Leads
                                @else
                                    <div class="p-2 bg-blue-50 dark:bg-blue-500/10 rounded-xl text-blue-600 dark:text-blue-400">
                                        <x-heroicon-s-user class="w-5 h-5" />
                                    </div>
                                    Detail Personal Leads
                                @endif
                            </h3>
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400 mt-1 ml-12 uppercase tracking-widest">
                                Data Masuk Bulan Ini
                            </p>
                        </div>
                        <button wire:click="$set('showLeadsModal', false)" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                            <x-heroicon-s-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="overflow-y-auto custom-scrollbar p-0 flex-1 bg-white dark:bg-zinc-900">
                        <table class="w-full text-sm text-left whitespace-nowrap">
                            <thead class="bg-slate-50 dark:bg-zinc-950/50 text-[10px] text-slate-400 uppercase font-black tracking-widest sticky top-0 z-20 backdrop-blur-sm">
                                <tr>
                                    <th class="px-6 py-4 pl-8">Tanggal</th>
                                    @if($leadsDetailType === 'corporate')
                                        <th class="px-6 py-4">Perusahaan & PIC</th>
                                        <th class="px-6 py-4">Est. Budget/Pax</th>
                                    @else
                                        <th class="px-6 py-4">Nama Jamaah</th>
                                        <th class="px-6 py-4">Kota / Domisili</th>
                                    @endif
                                    <th class="px-6 py-4">Sales PIC</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                @forelse($leadsData as $lead)
                                <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition group">
                                    
                                    <td class="px-6 py-4 pl-8">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-zinc-800 flex flex-col items-center justify-center text-xs font-black text-slate-500 dark:text-zinc-400 border border-slate-200 dark:border-white/5">
                                                <span>{{ $lead->created_at->format('d') }}</span>
                                                <span class="text-[8px] uppercase">{{ $lead->created_at->format('M') }}</span>
                                            </div>
                                            <span class="text-xs font-bold text-slate-500">{{ $lead->created_at->format('Y') }}</span>
                                        </div>
                                    </td>

                                    @if($leadsDetailType === 'corporate')
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-900 dark:text-white text-sm mb-0.5">{{ $lead->company_name }}</p>
                                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                                <x-heroicon-m-user class="w-3 h-3" />
                                                {{ $lead->pic_name }} 
                                                <span class="text-slate-300">•</span> 
                                                {{ $lead->pic_phone }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="font-mono font-bold text-slate-700 dark:text-zinc-300 text-sm">
                                                Rp {{ number_format($lead->budget_estimation, 0, ',', '.') }}
                                            </p>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 mt-1 rounded bg-slate-100 dark:bg-zinc-800 text-[10px] font-bold text-slate-500">
                                                <x-heroicon-m-users class="w-3 h-3" /> {{ $lead->potential_pax }} Pax
                                            </span>
                                        </td>
                                    @else
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 flex items-center justify-center font-black text-xs">
                                                    {{ substr($lead->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-900 dark:text-white text-sm">{{ $lead->name }}</p>
                                                    <p class="text-xs text-slate-500">{{ $lead->phone }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="flex items-center gap-1 text-xs font-medium text-slate-600 dark:text-zinc-400">
                                                <x-heroicon-s-map-pin class="w-3 h-3 text-slate-400" />
                                                {{ $lead->city ?? '-' }}
                                            </span>
                                        </td>
                                    @endif

                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 text-xs font-bold border border-slate-200 dark:border-white/5">
                                            {{ $lead->sales->full_name ?? 'Unassigned' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $status = strtolower($lead->status);
                                            $color = match ($status) {
                                                'hot', 'deal', 'closing' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
                                                'warm', 'negotiation' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
                                                'lost' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
                                                default => 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800'
                                            };
                                        @endphp
                                        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wide border {{ $color }}">
                                            {{ $lead->status }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center opacity-50">
                                            <x-heroicon-o-inbox class="w-12 h-12 text-slate-300 mb-2" />
                                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Belum ada leads tipe ini</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-5 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-zinc-900/80 flex justify-end shrink-0">
                        <button wire:click="$set('showLeadsModal', false)" class="px-6 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-black rounded-xl font-bold text-xs uppercase tracking-widest hover:opacity-90 transition shadow-lg">
                            Tutup Panel
                        </button>
                    </div>

                </div>
            </div>

        </main>

    </div>

</div>
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\OfficeWallet;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\UmrahPackage;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\WithFileUploads;
use App\Models\MediaAsset;
use App\Models\ContentRequest;

new #[Layout('layouts::app')] class extends Component {
    use WithFileUploads;
    // --- STATE UTAMA ---
    public $activeTab = 'payment';

    // --- STATE PAYMENT ---
    public $search = '';
    public $bookingId = null;
    public $amountRaw = '';
    public $cleanAmount = 0;
    public $notes = '';
    public $paymentMethod = 'cash';
    public $targetWalletId = null;
    public $showConfirmModal = false;

    // --- STATE EXPENSE ---
    public $expenseDesc = '';
    public $expenseAmountRaw = '';
    public $expenseCategoryId = null;
    public $expenseWalletId = null;
    public $showExpenseModal = false;

    // --- STATE SETOR TUNAI (DEPOSIT) ---
    public $showDepositModal = false;
    public $depositAmountRaw = '';
    public $depositTargetBankId = null;

    public $lastPaymentId = null;

    public $showMediaModal = false;
    public $mediaTab = 'upload';
    public $mediaPhotos = [];
    public $mediaTags;
    public $selectedPackageId;

    // State Request
    public $reqTitle, $reqDesc, $reqDeadline, $reqPriority = 'medium';

    public $showProofModal = false;
    public $proofUrl = null;
    public $proofType = 'image';

    // --- INIT ---
    public function mount()
    {
        $this->targetWalletId = OfficeWallet::where('type', 'cashier')->first()?->id;
        $this->expenseCategoryId = ExpenseCategory::first()?->id;
        $this->expenseWalletId = OfficeWallet::where('type', 'petty_cash')->first()?->id;

        $this->depositTargetBankId = OfficeWallet::where('type', 'bank')->first()?->id;
    }

    // --- COMPUTED PROPERTIES ---

    public function getSelectedBookingProperty()
    {
        if (!$this->bookingId)
            return null;
        return Booking::query()
            ->with(['jamaah', 'payments' => fn($q) => $q->orderBy('created_at', 'desc')])
            ->withSum(['payments' => fn($q) => $q->whereNotNull('verified_at')], 'amount')
            ->find($this->bookingId);
    }

    public function getJamaahResultsProperty()
    {
        if (strlen($this->search) < 2)
            return [];
        return Booking::with(['jamaah'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->where('booking_code', 'like', "%{$this->search}%")
                    ->orWhereHas('jamaah', fn($j) => $j->where('name', 'like', "%{$this->search}%"));
            })->limit(10)->get();
    }

    public function getWalletsProperty()
    {
        return OfficeWallet::whereIn('type', ['cashier', 'bank'])
            ->get()
            ->map(function ($wallet) {
                $wallet->icon = match ($wallet->type) {
                    'cashier' => 'heroicon-o-banknotes',
                    'bank' => 'heroicon-o-credit-card',
                };

                return $wallet;
            });
    }

    public function getSpendingWalletsProperty()
    {
        return OfficeWallet::whereIn('type', ['petty_cash', 'cashier'])
            ->get();
    }
    public function getCategoriesProperty()
    {
        return ExpenseCategory::all();
    }

    public function getPendingPaymentsProperty()
    {
        return Payment::with(['booking.jamaah', 'officeWallet'])
            ->whereNull('verified_at')
            ->latest()
            ->get();
    }

    // DATA LAPORAN & HEADER
    public function getTodayIncomeProperty()
    {
        return Payment::whereDate('created_at', today())
            ->whereNotNull('verified_at')
            ->sum('amount');
    }
    public function getTodayTransferIncomeProperty()
    {
        return Payment::whereDate('created_at', today())
            ->where('method', 'transfer')
            ->whereNotNull('verified_at')
            ->sum('amount');
    }
    public function getTotalBalanceProperty()
    {
        return OfficeWallet::where('type', 'cashier')
            ->sum('balance');
    }

    public function getTodayOperationalExpenseProperty()
    {
        return $this->todayExpenses->filter(function ($expense) {
            return $expense->wallet && $expense->wallet->type === 'petty_cash';
        })->sum('amount');
    }

    public function getTodayHppExpenseProperty()
    {
        return $this->todayExpenses->filter(function ($expense) {
            return $expense->wallet && $expense->wallet->type !== 'petty_cash';
        })->sum('amount');
    }
    // Saldo Cash
    public function getPettyCashBalanceProperty()
    {
        return OfficeWallet::where('type', 'petty_cash')->sum('balance');
    }

    // DATA BANK 
    public function getBankWalletsProperty()
    {
        return OfficeWallet::where('type', 'bank')
            ->withSum(['payments' => fn($q) => $q->whereDate('created_at', today())->whereNotNull('verified_at')], 'amount')
            ->get();
    }

    public function getTodayExpensesProperty()
    {
        return Expense::with(['category', 'wallet'])
            ->whereDate('transaction_date', today())
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // --- ACTIONS: NAVIGATION ---
    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetSelection();
    }
    public function selectBooking($id)
    {
        $this->bookingId = $id;
        $this->search = '';
        $this->amountRaw = '';
    }
    public function updatedPaymentMethod($value)
    {
        $this->targetWalletId = ($value === 'cash')
            ? OfficeWallet::where('type', 'cashier')->first()?->id
            : OfficeWallet::where('type', 'bank')->first()?->id;
    }

    // --- ACTION: PAYMENT ---
    public function resetSelection()
    {
        $this->bookingId = null;
        $this->amountRaw = '';
        $this->notes = '';
        $this->showConfirmModal = false;
        $this->showExpenseModal = false;
        $this->showDepositModal = false;
    }

    public function askToPay()
    {
        $this->cleanAmount = (int) str_replace(['.', ','], '', $this->amountRaw);
        if (!$this->bookingId || $this->cleanAmount <= 0) {
            session()->flash('error', 'Nominal tidak valid!');
            return;
        }
        $this->showConfirmModal = true;
    }

    public function processPayment()
    {
        $nominal = $this->cleanAmount;

        $newPaymentId = DB::transaction(function () use ($nominal) {

            $alreadyPaid = $this->selectedBooking->payments()
                ->whereNotNull('verified_at')
                ->sum('amount');

            $totalPrice = $this->selectedBooking->total_price;

            if ($alreadyPaid == 0) {
                $type = 'dp';
            } elseif (($alreadyPaid + $nominal) >= $totalPrice) {
                $type = 'pelunasan';
            } else {
                $type = 'cicilan';
            }

            $payment = Payment::create([
                'booking_id' => $this->bookingId,
                'amount' => $nominal,
                'type' => $type,
                'method' => $this->paymentMethod,
                'proof_file' => null,
                'created_at' => now(),
                'verified_at' => now(),
                'verified_by' => auth()->user()->employee?->id ?? auth()->id(),
                'office_wallet_id' => $this->targetWalletId,
            ]);

            if ($this->targetWalletId) {
                OfficeWallet::where('id', $this->targetWalletId)->increment('balance', $nominal);
            }

            return $payment->id;
        });

        $this->dispatch('open-invoice-url', url: route('print.invoice', ['id' => $newPaymentId]));

        session()->flash('success', 'Pembayaran Rp ' . number_format($nominal, 0, ',', '.') . ' Diterima!');

        $this->resetSelection();
    }

    // --- ACTION: EXPENSE---
    public function askToSaveExpense()
    {
        $this->cleanAmount = (int) str_replace(['.', ','], '', $this->expenseAmountRaw);
        if ($this->cleanAmount <= 0 || empty($this->expenseDesc)) {
            session()->flash('error', 'Data tidak lengkap!');
            return;
        }
        $this->showExpenseModal = true;
    }

    public function saveExpense()
    {
        $nominal = $this->cleanAmount;
        DB::transaction(function () use ($nominal) {
            $wallet = OfficeWallet::find($this->expenseWalletId);
            if ($wallet->balance < $nominal)
                throw new \Exception("Saldo {$wallet->name} Tidak Cukup!");

            Expense::create([
                'expense_category_id' => $this->expenseCategoryId,
                'office_wallet_id' => $wallet->id,
                'transaction_date' => now(),
                'name' => $this->expenseDesc,
                'amount' => $nominal,
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'note' => 'POS Expense'
            ]);
            $wallet->decrement('balance', $nominal);
        });
        session()->flash('success', 'Pengeluaran Dicatat!');
        $this->expenseAmountRaw = '';
        $this->expenseDesc = '';
        $this->showExpenseModal = false;
    }

    // --- ACTION: SETOR TUNAI / HANDOVER ---
    public function askToDeposit()
    {
        $this->showDepositModal = true;
    }

    public function processDeposit()
    {
        $nominal = (int) str_replace(['.', ','], '', $this->depositAmountRaw);
        if ($nominal <= 0) {
            session()->flash('error', 'Nominal Salah!');
            return;
        }

        DB::transaction(function () use ($nominal) {
            $laci = OfficeWallet::where('type', 'cashier')->first();
            $bank = OfficeWallet::find($this->depositTargetBankId);

            if ($laci->balance < $nominal)
                throw new \Exception("Uang di Laci Kurang!");

            $laci->decrement('balance', $nominal);

            $bank->increment('balance', $nominal);

            $cat = ExpenseCategory::firstOrCreate(['name' => 'Setor Tunai / Handover']);

            Expense::create([
                'expense_category_id' => $cat->id,
                'office_wallet_id' => $laci->id,
                'transaction_date' => now(),
                'name' => "Setor Tunai ke {$bank->name}",
                'amount' => $nominal,
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'note' => 'Internal Transfer POS'
            ]);
        });

        session()->flash('success', 'Setor Tunai Berhasil!');
        $this->showDepositModal = false;
        $this->depositAmountRaw = '';
    }

    // Action Verifikasi
    public function verifyIncomingPayment($paymentId)
    {
        $payment = Payment::find($paymentId);

        // 1. Update Payment jadi Verified
        $payment->update([
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        // 2. Tambah Saldo ke Wallet Tujuan (Misal: Bank BCA)
        $wallet = OfficeWallet::find($payment->office_wallet_id);
        $wallet->increment('balance', $payment->amount);

        // 3. Catat Transaksi Masuk (Cashflow)
        // Payment::create([
        //     'office_wallet_id' => $wallet->id,
        //     'type' => 'income',
        //     'amount' => $payment->amount,
        //     'description' => "Pembayaran Booking: " . $payment->booking->booking_code . " (" . $payment->booking->jamaah->name . ")",
        //     'reference_id' => $payment->id,
        //     'category' => 'sales_booking',
        //     'user_id' => auth()->id(),
        // ]);

        Notification::make()->title('Pembayaran Diverifikasi & Saldo Bertambah 💰')->success()->send();
    }

    public function viewProof($paymentId)
    {
        $payment = Payment::find($paymentId);

        if ($payment && $payment->proof_file) {
            $this->proofUrl = asset('storage/' . $payment->proof_file);

            $ext = pathinfo($payment->proof_file, PATHINFO_EXTENSION);
            $this->proofType = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp']) ? 'image' : 'file';

            $this->showProofModal = true;
        } else {
            Notification::make()->title('File tidak ditemukan')->danger()->send();
        }
    }

    // --- PRINT ---
    public function closeRegister()
    {
        return redirect()->away(route('print.daily_report'));
    }

    public function getPackagesProperty()
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

            if ($this->mediaTags) {
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

<div class="flex flex-col h-full w-full relative bg-slate-50 dark:bg-[#09090b]">
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
                <div
                    class="w-10 h-10 bg-gradient-to-br from-primary to-orange-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
                    <x-heroicon-s-currency-dollar class="w-6 h-6" />
                </div>
                <div class="flex flex-col">
                    <span class="font-black text-sm md:text-base tracking-tight leading-none uppercase">Finance <span
                            class="text-primary">Center</span></span>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-zinc-500 tracking-widest uppercase">Finance
                            Command Center</span>
                    </div>
                </div>
            </div>
        
        </div>

        <div class="flex items-center gap-2 md:gap-4">
            <button @click="toggleTheme()" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-all duration-300">
                <x-heroicon-s-moon class="w-5 h-5" x-show="!darkMode" />
                <x-heroicon-s-sun class="w-6 h-6 text-primary" x-show="darkMode" x-cloak />
            </button>

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center gap-2 p-1 pr-3 rounded-full bg-slate-100 dark:bg-zinc-800 hover:ring-2 hover:ring-primary/30 transition-all cursor-pointer">
                    <div class="h-7 w-7 rounded-full bg-primary flex items-center justify-center text-white font-black text-[10px] shadow-sm">
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
                        x-cloak>
                    
                    <div class="px-4 py-3 bg-slate-50 dark:bg-white/5 mb-2">
                        <p class="text-xs font-black text-slate-900 dark:text-white truncate uppercase tracking-wider">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                    </div>

                    <a href="/admin" class="group flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-primary dark:text-zinc-400 dark:hover:text-white transition-all">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-zinc-800 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
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
                            Sign Out System
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 w-full relative overflow-hidden bg-slate-50 dark:bg-[#09090b]">
        <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.01] pointer-events-none overflow-hidden">
            <i class="bi bi-bank2 absolute -bottom-10 -left-10 text-[20rem]"></i>
        </div>
        
        <div class="relative z-10 h-full">
            <div class="h-full flex flex-col md:flex-row bg-zinc-100 dark:bg-zinc-950 overflow-hidden relative">
    
                <aside class="hidden md:flex w-20 bg-white dark:bg-zinc-900 border-r border-gray-200 dark:border-white/5 flex-col items-center py-8 gap-8 z-20 shadow-sm shrink-0">

                    <button
                        wire:click="setTab('payment')"
                        class="group relative flex flex-col items-center gap-1.5 transition-all duration-300
                        {{ $activeTab === 'payment' ? 'text-primary' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                        
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 
                            {{ $activeTab === 'payment' ? 'bg-primary text-white shadow-lg shadow-orange-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-primary/10' }}">
                            <x-heroicon-s-banknotes class="w-6 h-6" />
                        </div>
                        <span class="text-[9px] uppercase font-black tracking-tighter">Payment</span>
                        
                        @if($activeTab === 'payment')
                            <div class="absolute -right-[21px] w-1 h-8 bg-primary rounded-l-full"></div>
                        @endif
                    </button>

                    <button
                        wire:click="setTab('expense')"
                        class="group relative flex flex-col items-center gap-1.5 transition-all duration-300
                        {{ $activeTab === 'expense' ? 'text-red-500' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                        
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 
                            {{ $activeTab === 'expense' ? 'bg-red-500 text-white shadow-lg shadow-red-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-red-500/10' }}">
                            <x-heroicon-s-receipt-refund class="w-6 h-6" />
                        </div>
                        <span class="text-[9px] uppercase font-black tracking-tighter">Expense</span>

                        @if($activeTab === 'expense')
                            <div class="absolute -right-[21px] w-1 h-8 bg-red-500 rounded-l-full"></div>
                        @endif
                    </button>

                    <button
                        wire:click="setTab('report')"
                        class="group relative flex flex-col items-center gap-1.5 transition-all duration-300
                        {{ $activeTab === 'report' ? 'text-purple-500' : 'text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300' }}">
                        
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 
                            {{ $activeTab === 'report' ? 'bg-purple-500 text-white shadow-lg shadow-purple-500/30' : 'bg-slate-100 dark:bg-white/5 group-hover:bg-purple-500/10' }}">
                            <x-heroicon-s-chart-bar class="w-6 h-6" />
                        </div>
                        <span class="text-[9px] uppercase font-black tracking-tighter">Laporan</span>

                        @if($activeTab === 'report')
                            <div class="absolute -right-[21px] w-1 h-8 bg-purple-500 rounded-l-full"></div>
                        @endif
                    </button>

                    <div class="flex-1"></div>

                    <button wire:click="$set('showMediaModal', true)" 
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 hover:bg-primary hover:text-white dark:bg-white/5 dark:text-zinc-500 transition-all duration-300 flex items-center justify-center shadow-sm">
                        <x-heroicon-s-camera class="w-5 h-5" />
                    </button>

                </aside>

                <main class="flex-1 flex flex-col h-full overflow-hidden relative">
                    
                    <div class="p-4 mb-2 md:mb-6 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-md border-b border-gray-200 dark:border-white/5 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center shrink-0 gap-4">
                
                        <div class="relative">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-2xl {{ $activeTab == 'payment' ? 'bg-emerald-500/10' : ($activeTab == 'expense' ? 'bg-red-500/10' : 'bg-purple-500/10') }} transition-colors">
                                    @if($activeTab == 'payment')
                                        <x-heroicon-s-banknotes class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                                    @elseif($activeTab == 'expense')
                                        <x-heroicon-s-receipt-refund class="w-6 h-6 text-red-600 dark:text-red-400" />
                                    @elseif($activeTab == 'report')
                                        <x-heroicon-s-chart-bar class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    @endif
                                </div>
                                
                                <div>
                                    <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                                        @if($activeTab == 'payment')
                                            Pemasukan
                                        @elseif($activeTab == 'expense')
                                            Pengeluaran
                                        @elseif($activeTab == 'report')
                                            Laporan
                                        @endif
                                    </h1>
                                    <p class="text-[10px] md:text-xs text-slate-500 dark:text-zinc-500 font-bold uppercase tracking-widest flex items-center gap-1.5">
                                        Finance <i class="bi bi-chevron-right text-[8px]"></i> 
                                        <span class="text-primary">Management Control</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between w-full md:w-auto md:justify-end gap-3">
                            
                            <button wire:click="$set('showMediaModal', true)" 
                                class="md:hidden flex items-center gap-2 bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 px-4 py-2.5 rounded-xl transition shadow-sm border border-slate-200 dark:border-white/10 active:scale-95">
                                <x-heroicon-s-camera class="w-4 h-4 text-primary" />
                                <span class="text-xs font-black uppercase tracking-wider">Media</span>
                            </button>

                            <div class="flex items-center gap-3 bg-slate-100 dark:bg-white/5 px-4 py-2 md:py-2.5 rounded-2xl border border-slate-200/50 dark:border-white/5">
                                <div class="hidden md:flex flex-col items-end">
                                    <span class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest leading-none mb-1">Hari Ini</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-zinc-300">{{ now()->isoFormat('dddd') }}</span>
                                </div>
                                <div class="w-[1px] h-6 bg-slate-300 dark:bg-zinc-700 hidden md:block"></div>
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-calendar3 text-primary text-sm"></i>
                                    <span class="text-sm md:text-base font-black text-slate-900 dark:text-white">
                                        {{ now()->format('d M Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="flex-1 overflow-hidden px-2 md:px-4 pb-20 md:pb-4 custom-scrollbar overflow-y-auto">
                        
                        @if($activeTab === 'payment')
                        <div class="space-y-6 pb-24 md:pb-6 animate-fade-in">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-emerald-500 dark:bg-emerald-600/20 p-5 rounded-[2rem] border border-emerald-400/30 shadow-lg shadow-emerald-500/10 relative overflow-hidden group">
                                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                                    <p class="text-[10px] md:text-xs text-emerald-100 dark:text-emerald-400 font-black uppercase tracking-[0.2em] mb-2">Total Masuk Hari Ini</p>
                                    <p class="text-2xl md:text-4xl font-black text-white leading-none">
                                        <span class="text-lg md:text-xl opacity-70">Rp</span> {{ number_format($this->todayIncome, 0, ',', '.') }}
                                    </p>
                                    <div class="mt-4 flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-white">
                                            <x-heroicon-s-arrow-trending-up class="w-5 h-5" />
                                        </div>
                                        <span class="text-[10px] font-bold text-emerald-100 uppercase tracking-widest">Real-time Stream</span>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-zinc-900 p-5 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-xl relative group cursor-help transition-all duration-300 hover:border-purple-500/50"
                                    x-data="{ showTooltip: false }"
                                    @mouseenter="showTooltip = true"
                                    @mouseleave="showTooltip = false"
                                    @click="showTooltip = !showTooltip">
                                    
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[10px] md:text-xs text-purple-600 dark:text-purple-400 font-black uppercase tracking-[0.2em]">Via Transfer</p>
                                        <div class="w-6 h-6 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                            <x-heroicon-s-information-circle class="w-4 h-4 text-purple-500" />
                                        </div>
                                    </div>
                                    
                                    <p class="text-2xl md:text-4xl font-black text-slate-900 dark:text-white leading-none">
                                        <span class="text-lg md:text-xl text-slate-400">Rp</span> {{ number_format($this->todayTransferIncome, 0, ',', '.') }}
                                    </p>

                                    <div x-show="showTooltip" 
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        class="absolute z-[60] left-0 right-0 top-full mt-3 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-200 dark:border-white/10 p-5"
                                        x-cloak>
                                        <h5 class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest border-b border-slate-100 dark:border-white/5 pb-3 mb-3 flex items-center gap-2">
                                            <x-heroicon-s-building-library class="w-4 h-4" /> Saldo Per Rekening
                                        </h5>
                                        <div class="space-y-3">
                                            @foreach($this->bankWallets as $bank)
                                            <div class="flex justify-between items-center group/bank">
                                                <span class="text-xs font-bold text-slate-600 dark:text-zinc-400 group-hover/bank:text-primary transition-colors">{{ $bank->name }}</span>
                                                <span class="text-sm font-black text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-3 py-1 rounded-full">
                                                    {{ number_format($bank->balance / 1000, 0) }}k
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-zinc-900 p-5 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-xl transition-all duration-300 hover:border-blue-500/50">
                                    <p class="text-[10px] md:text-xs text-blue-600 dark:text-blue-400 font-black uppercase tracking-[0.2em] mb-2">Cash (Laci Kasir)</p>
                                    <p class="text-2xl md:text-4xl font-black text-slate-900 dark:text-white leading-none">
                                        <span class="text-lg md:text-xl text-slate-400">Rp</span> {{ number_format($this->totalBalance, 0, ',', '.') }}
                                    </p>
                                    <div class="mt-4 flex items-center gap-2 text-blue-500 font-bold text-[10px] uppercase tracking-widest">
                                        <x-heroicon-s-banknotes class="w-4 h-4" /> Ready for Expense
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-auto lg:h-[calc(100vh-280px)]">
                                
                                <div class="lg:col-span-4 bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-xl p-6 flex flex-col border border-slate-100 dark:border-white/5 overflow-hidden">
                                    
                                    <div class="relative mb-6">
                                        <input wire:model.live.debounce.300ms="search" type="text" 
                                            placeholder="Cari Nama / Kode Booking..." 
                                            class="w-full pl-12 pr-6 py-4 bg-slate-100 dark:bg-white/5 border-transparent rounded-2xl focus:bg-white dark:focus:bg-zinc-800 focus:ring-4 focus:ring-primary/20 transition-all text-sm font-bold dark:text-white outline-none">
                                        <x-heroicon-s-magnifying-glass class="w-6 h-6 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" />
                                    </div>

                                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-4">
                                        
                                        @if($this->pendingPayments && $this->pendingPayments->count() > 0)
                                        <div class="bg-orange-500/5 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-3xl p-4">
                                            <div class="flex items-center gap-2 mb-4">
                                                <span class="relative flex h-3 w-3">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span>
                                                </span>
                                                <h3 class="text-xs font-black text-orange-700 dark:text-orange-400 uppercase tracking-widest">Antrean Verifikasi ({{ $this->pendingPayments->count() }})</h3>
                                            </div>

                                            <div class="space-y-3">
                                                @foreach($this->pendingPayments as $payment)
                                                <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-orange-100 dark:border-orange-900/30 group">
                                                    <div class="flex justify-between items-start">
                                                        <div>
                                                            <h4 class="text-xs font-black text-slate-800 dark:text-zinc-200 uppercase">{{ $payment->booking->jamaah->name }}</h4>
                                                            <p class="text-[10px] text-slate-500">{{ $payment->booking->booking_code }}</p>
                                                        </div>
                                                        <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-white/5 text-[9px] font-black uppercase text-slate-500">{{ $payment->method }}</span>
                                                    </div>
                                                    
                                                    <p class="text-xl font-black text-emerald-600 my-2">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                                                    
                                                    <div class="flex gap-2 mt-4">
                                                        @if($payment->proof_file)
                                                        <button wire:click="viewProof({{ $payment->id }})" class="flex-1 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 text-[10px] font-black uppercase py-2.5 rounded-xl transition">Bukti</button>
                                                        @endif
                                                        <button wire:click="verifyIncomingPayment({{ $payment->id }})" 
                                                                wire:confirm="Verifikasi uang masuk?" 
                                                                class="flex-[2] bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-black uppercase py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 transition-all active:scale-95">
                                                            Verify
                                                        </button>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif

                                        <div class="space-y-2">
                                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2">Hasil Pencarian</h3>
                                            @forelse($this->jamaahResults as $item)
                                                <div wire:click="selectBooking({{ $item->id }})" 
                                                    class="p-4 bg-white dark:bg-white/5 border border-slate-100 dark:border-white/5 rounded-2xl hover:border-primary/50 hover:shadow-lg transition-all cursor-pointer group flex items-center justify-between">
                                                    <div>
                                                        <p class="font-black text-sm text-slate-800 dark:text-zinc-200 group-hover:text-primary transition-colors uppercase">{{ $item->jamaah->name }}</p>
                                                        <p class="text-[10px] text-slate-500 tracking-wider">{{ $item->booking_code }}</p>
                                                    </div>
                                                    <x-heroicon-s-chevron-right class="w-5 h-5 text-slate-300 group-hover:text-primary transition-all" />
                                                </div>
                                            @empty
                                                @if(strlen($search) >= 2)
                                                    <div class="py-12 text-center">
                                                        <i class="bi bi-search text-4xl text-slate-200 dark:text-zinc-800"></i>
                                                        <p class="text-xs font-bold text-slate-400 mt-2">Jamaah tidak ditemukan</p>
                                                    </div>
                                                @endif
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                <div class="lg:col-span-8 bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-xl flex flex-col border border-slate-100 dark:border-white/5 overflow-hidden relative">
                                    
                                    @if($this->selectedBooking)
                                        @php
                                            $totalBayar = $this->selectedBooking->payments_sum_amount ?? 0;
                                            $sisaTagihan = $this->selectedBooking->total_price - $totalBayar;
                                        @endphp
                                        
                                        <div class="p-6 md:p-8 bg-slate-50 dark:bg-zinc-950/50 border-b dark:border-white/5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-14 h-14 bg-primary text-white rounded-2xl flex items-center justify-center font-black text-xl shadow-lg shadow-orange-500/20">
                                                    {{ substr($this->selectedBooking->jamaah->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $this->selectedBooking->jamaah->name }}</h2>
                                                    <p class="text-xs font-bold text-primary tracking-widest">{{ $this->selectedBooking->booking_code }}</p>
                                                </div>
                                            </div>
                                            <div class="bg-white dark:bg-zinc-900 px-6 py-3 rounded-2xl shadow-sm border border-red-100 dark:border-red-500/20">
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center md:text-right">Sisa Tagihan</p>
                                                <p class="text-2xl font-black text-red-500">Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</p>
                                            </div>
                                        </div>

                                        <div class="flex-1 overflow-y-auto p-6 md:p-8 grid grid-cols-1 xl:grid-cols-2 gap-8 custom-scrollbar">
                                            
                                            <div class="space-y-6">
                                                <div>
                                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Nominal Bayar (IDR)</label>
                                                    <div class="relative group">
                                                        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-2xl font-black text-slate-300 dark:text-zinc-600">Rp</div>
                                                        <input wire:model="amountRaw" type="text" x-on:input="$el.value = formatRupiah($el.value)" 
                                                            class="w-full text-3xl font-black text-right pl-16 pr-6 py-6 bg-slate-50 dark:bg-white/5 border-2 border-slate-200 dark:border-white/10 rounded-[2rem] focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none text-primary" 
                                                            placeholder="0">
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4">
                                                    <label class="cursor-pointer">
                                                        <input type="radio" wire:model.live="paymentMethod" value="cash" class="peer sr-only">
                                                        <div class="p-4 border-2 border-slate-100 dark:border-white/5 rounded-2xl text-center font-black text-xs uppercase tracking-widest text-slate-400 peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all flex flex-col items-center gap-2">
                                                            <x-heroicon-s-banknotes class="w-6 h-6" /> Tunai / Cash
                                                        </div>
                                                    </label>
                                                    <label class="cursor-pointer">
                                                        <input type="radio" wire:model.live="paymentMethod" value="transfer" class="peer sr-only">
                                                        <div class="p-4 border-2 border-slate-100 dark:border-white/5 rounded-2xl text-center font-black text-xs uppercase tracking-widest text-slate-400 peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all flex flex-col items-center gap-2">
                                                            <x-heroicon-s-building-library class="w-6 h-6" /> Transfer Bank
                                                        </div>
                                                    </label>
                                                </div>

                                                <div class="grid grid-cols-1 gap-4">
                
                                                    <div>
                                                        <label class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest ml-1 mb-2 block">Target Penyimpanan</label>
                                                        
                                                        <div class="relative group">
                                                            <select wire:model="targetWalletId" 
                                                                    class="w-full p-4 pl-12 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-bold text-sm text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all appearance-none cursor-pointer">
                                                                
                                                                @foreach($this->wallets as $wallet)
                                                                    @if($paymentMethod == 'cash' && $wallet->type == 'cashier')
                                                                        <option value="{{ $wallet->id }}" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                                            {{ $wallet->name }} (Kasir Utama)
                                                                        </option>
                                                                    @elseif($paymentMethod == 'transfer' && $wallet->type == 'bank')
                                                                        <option value="{{ $wallet->id }}" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                                            {{ $wallet->name }}
                                                                        </option>
                                                                    @endif
                                                                @endforeach
                                                            
                                                            </select>

                                                            <x-heroicon-s-wallet class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 group-focus-within:text-indigo-500 transition-colors" />
                                                            
                                                            <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest ml-1 mb-2 block">Catatan / Keterangan</label>
                                                        
                                                        <div class="relative group">
                                                            <textarea wire:model="notes" 
                                                                    class="w-full p-4 pl-12 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-medium text-sm text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-400" 
                                                                    rows="2" 
                                                                    placeholder="Keterangan tambahan..."></textarea>
                                                            
                                                            <x-heroicon-s-pencil-square class="w-5 h-5 text-slate-400 absolute left-4 top-4 group-focus-within:text-indigo-500 transition-colors" />
                                                        </div>
                                                    </div>

                                                </div>

                                                <button wire:click="askToPay" 
                                                        class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white text-lg font-black py-5 rounded-[2rem] shadow-xl shadow-emerald-500/20 transition-all transform active:scale-95 flex items-center justify-center gap-3">
                                                    <x-heroicon-s-check-badge class="w-6 h-6" /> PROSES PEMBAYARAN
                                                </button>
                                                
                                                <button wire:click="resetSelection" class="w-full text-slate-400 hover:text-red-500 font-bold text-xs uppercase tracking-widest transition-colors">Batal & Pilih Jamaah Lain</button>
                                            </div>

                                            <div class="bg-slate-50 dark:bg-zinc-950/30 p-6 rounded-[2rem] border border-slate-200 dark:border-white/5">
                                                <h3 class="text-sm font-black text-slate-900 dark:text-white mb-6 uppercase tracking-widest flex items-center gap-2">
                                                    <x-heroicon-s-clock class="w-5 h-5 text-primary" /> Riwayat Cicilan
                                                </h3>
                                                <div class="space-y-4 max-h-[450px] overflow-y-auto custom-scrollbar pr-2">
                                                    @forelse($this->selectedBooking->payments as $history)
                                                        <div class="p-4 bg-white dark:bg-zinc-900 rounded-2xl border border-slate-100 dark:border-white/5 shadow-sm group">
                                                            <div class="flex justify-between items-center">
                                                                <div>
                                                                    <p class="font-black text-sm text-slate-900 dark:text-zinc-200">Rp {{ number_format($history->amount, 0, ',', '.') }}</p>
                                                                    <p class="text-[10px] font-bold text-slate-400">{{ $history->created_at->format('d M Y - H:i') }}</p>
                                                                </div>
                                                                <div class="text-right">
                                                                    <span class="text-[9px] font-black px-2 py-1 rounded-full uppercase {{ $history->verified_at ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-orange-100 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400' }}">
                                                                        {{ $history->verified_at ? 'Verified' : 'Pending' }}
                                                                    </span>
                                                                    <p class="text-[9px] font-black text-slate-300 mt-1 uppercase tracking-tighter">{{ $history->method }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="text-center py-20 opacity-30">
                                                            <x-heroicon-o-document-magnifying-glass class="w-12 h-12 mx-auto mb-2" />
                                                            <p class="text-xs font-bold uppercase tracking-widest">Belum ada transaksi</p>
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex-1 flex flex-col items-center justify-center p-12 text-center">
                                            <div class="w-32 h-32 bg-slate-50 dark:bg-white/5 rounded-[3rem] flex items-center justify-center mb-6">
                                                <x-heroicon-o-cursor-arrow-ripple class="w-16 h-16 text-slate-200 dark:text-zinc-200 animate-pulse" />
                                            </div>
                                            <h3 class="text-xl font-black text-slate-400 dark:text-zinc-700 uppercase tracking-widest">Pilih Data Jamaah</h3>
                                            <p class="text-sm text-slate-400 max-w-xs mt-2">Gunakan fitur pencarian di sebelah kiri untuk memilih jamaah dan memproses pembayaran.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($activeTab === 'expense')
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-auto lg:h-[calc(100vh-220px)] animate-fade-in pb-24 md:pb-0">
                            
                            <div class="lg:col-span-8 bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-xl flex flex-col border border-slate-100 dark:border-white/5 overflow-hidden">
                                
                                <div class="p-6 md:p-8 bg-red-50/50 dark:bg-red-500/5 border-b border-red-100 dark:border-red-500/10 flex justify-between items-center">
                                    <div>
                                        <h2 class="text-xl md:text-2xl font-black text-red-600 dark:text-red-400 uppercase tracking-tight flex items-center gap-3">
                                            <x-heroicon-s-receipt-refund class="w-6 h-6 md:w-8 md:h-8" />
                                            Catat Pengeluaran
                                        </h2>
                                        <p class="text-[10px] md:text-xs font-bold text-slate-500 dark:text-zinc-500 uppercase tracking-[0.2em] mt-1">Operational & HPP Tracking</p>
                                    </div>
                                    <div class="hidden md:block">
                                        <span class="bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400 text-[10px] font-black px-4 py-2 rounded-full uppercase tracking-widest border border-red-200 dark:border-red-500/20">
                                            High Priority
                                        </span>
                                    </div>
                                </div>

                                <div class="p-6 md:p-10">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10">
                                        <div class="space-y-6">
                                            <div class="space-y-6">
                
                                                <div>
                                                    <label class="block text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-3 ml-1">
                                                        Sumber Dana (Wallet)
                                                    </label>
                                                    <div class="relative group">
                                                        <select wire:model="expenseWalletId" 
                                                                class="w-full p-4 pl-12 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-bold text-sm text-slate-700 dark:text-zinc-200 focus:border-red-500/50 focus:ring-4 focus:ring-red-500/10 outline-none transition-all appearance-none cursor-pointer">
                                                            
                                                            <option value="" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">-- Pilih Wallet --</option>

                                                            @foreach($this->spendingWallets as $wallet)
                                                                <option value="{{ $wallet->id }}" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                                    {{ $wallet->name }} (Rp {{ number_format($wallet->balance / 1000, 0) }}k)
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        
                                                        <x-heroicon-s-wallet class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 group-focus-within:text-red-500 transition-colors" />
                                                        
                                                        <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-3 ml-1">
                                                        Kategori Biaya
                                                    </label>
                                                    <div class="relative group">
                                                        <select wire:model="expenseCategoryId" 
                                                                class="w-full p-4 pl-12 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-bold text-sm text-slate-700 dark:text-zinc-200 focus:border-red-500/50 focus:ring-4 focus:ring-red-500/10 outline-none transition-all appearance-none cursor-pointer">
                                                            
                                                            <option value="" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">-- Pilih Kategori --</option>

                                                            @foreach($this->categories as $cat)
                                                                <option value="{{ $cat->id }}" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                                    {{ $cat->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        
                                                        <x-heroicon-s-tag class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 group-focus-within:text-red-500 transition-colors" />
                                                        
                                                        <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="space-y-6">
                                            <div>
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Nominal Pengeluaran (IDR)</label>
                                                <div class="relative group">
                                                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-2xl font-black text-red-300 dark:text-red-900/50">Rp</div>
                                                    <input wire:model="expenseAmountRaw" type="text" x-on:input="$el.value = formatRupiah($el.value)" 
                                                        class="w-full text-3xl font-black text-right pl-16 pr-6 py-5 bg-red-500/5 dark:bg-red-500/10 border-2 border-red-100 dark:border-red-500/20 rounded-2xl focus:border-red-500 focus:ring-4 focus:ring-red-500/10 transition-all outline-none text-red-600 dark:text-red-400" 
                                                        placeholder="0">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Keterangan / Keperluan</label>
                                                <div class="relative group">
                                                    <textarea wire:model="expenseDesc" 
                                                            class="w-full p-4 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-medium text-sm md:text-base text-slate-700 dark:text-zinc-200 focus:border-red-500/50 focus:ring-4 focus:ring-red-500/10 outline-none transition-all h-24 md:h-28" 
                                                            placeholder="Contoh: Pembayaran listrik kantor bulan Feb..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-10">
                                        <button wire:click="askToSaveExpense" 
                                                class="w-full bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 text-white text-lg font-black py-5 rounded-2xl shadow-xl shadow-red-500/30 transition-all transform active:scale-95 flex items-center justify-center gap-3">
                                            <x-heroicon-s-paper-airplane class="w-6 h-6" />
                                            KONFIRMASI PENGELUARAN
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-4 bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-xl p-6 flex flex-col border border-slate-100 dark:border-white/5 overflow-hidden">
                                <div class="flex items-center justify-between mb-6 px-2">
                                    <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">History Hari Ini</h3>
                                    <div class="w-8 h-8 rounded-full bg-red-500/10 flex items-center justify-center">
                                        <x-heroicon-s-clock class="w-4 h-4 text-red-500" />
                                    </div>
                                </div>

                                <div class="flex-1 overflow-y-auto space-y-4 custom-scrollbar pr-2">
                                    @forelse($this->todayExpenses as $expense)
                                        <div class="group p-4 bg-slate-50 dark:bg-white/5 rounded-2xl border border-transparent hover:border-red-500/30 transition-all duration-300">
                                            <div class="flex justify-between items-start mb-3">
                                                <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest {{ str_contains(strtolower($expense->wallet->name ?? ''), 'petty') ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' }}">
                                                    {{ $expense->wallet->name ?? '-' }}
                                                </span>
                                                <span class="text-[10px] font-bold text-slate-400 group-hover:text-red-400 transition-colors">{{ $expense->created_at->format('H:i') }}</span>
                                            </div>
                                            <p class="font-bold text-sm text-slate-800 dark:text-zinc-200 leading-snug mb-2 group-hover:text-red-500 transition-colors">
                                                {{ $expense->name }}
                                            </p>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-1 opacity-40 text-[9px] font-black uppercase">
                                                    <x-heroicon-s-tag class="w-3 h-3" />
                                                    {{ $expense->category->name ?? 'General' }}
                                                </div>
                                                <p class="text-red-600 dark:text-red-400 font-black text-base italic">
                                                    - Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                                </p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center h-full opacity-20 py-10">
                                            <x-heroicon-o-document-minus class="w-16 h-16 mb-2" />
                                            <p class="text-xs font-black uppercase tracking-widest">Belum ada data</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($activeTab === 'report')
                        <div class="h-full p-4 md:p-8 animate-fade-in overflow-y-auto pb-32 custom-scrollbar">
                            
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                                <div class="relative">
                                    <h2 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Financial <span class="text-primary">Summary</span></h2>
                                    <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-[0.2em] mt-1">Laporan Arus Kas & Saldo Real-time</p>
                                </div>
                                
                                <button wire:click="askToDeposit" 
                                        class="w-full md:w-auto group bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200 px-8 py-4 rounded-[1.5rem] font-black shadow-xl border border-slate-200 dark:border-white/5 flex items-center justify-center gap-3 transition-all hover:bg-primary hover:text-white hover:border-primary active:scale-95">
                                    <x-heroicon-s-arrow-path-rounded-square class="w-6 h-6 text-primary group-hover:text-white transition-colors" />
                                    <span class="text-sm tracking-widest uppercase">Setor Tunai (Handover)</span>
                                </button>
                            </div>

                            <div class="mb-12">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                        <x-heroicon-s-chart-pie class="w-5 h-5" />
                                    </div>
                                    <h3 class="text-sm font-black text-slate-400 dark:text-zinc-500 uppercase tracking-[0.2em]">I. Pemasukan & Posisi Saldo</h3>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                    <div class="bg-emerald-500 p-6 rounded-[2rem] shadow-lg shadow-emerald-500/20 relative overflow-hidden group">
                                        <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition-all"></div>
                                        <p class="text-[10px] font-black text-emerald-100 uppercase tracking-widest opacity-80 mb-2">Total Uang Masuk</p>
                                        <p class="text-2xl font-black text-white leading-tight">Rp {{ number_format($this->todayIncome, 0, ',', '.') }}</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-xl transition-all hover:border-blue-500/50">
                                        <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-2">Fisik Laci Kasir</p>
                                        <p class="text-2xl font-black text-slate-900 dark:text-white leading-tight">Rp {{ number_format($this->totalBalance, 0, ',', '.') }}</p>
                                    </div>

                                    @foreach($this->bankWallets as $bank)
                                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-xl transition-all hover:border-purple-500/50">
                                        <div class="flex items-center justify-between mb-2">
                                            <p class="text-[10px] font-black text-purple-500 uppercase tracking-widest">{{ $bank->name }}</p>
                                            <x-heroicon-s-building-library class="w-4 h-4 text-slate-300" />
                                        </div>
                                        <p class="text-xl font-black text-slate-900 dark:text-white leading-tight">Rp {{ number_format($bank->balance, 0, ',', '.') }}</p>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-12">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center text-red-500">
                                        <x-heroicon-s-calculator class="w-5 h-5" />
                                    </div>
                                    <h3 class="text-sm font-black text-slate-400 dark:text-zinc-500 uppercase tracking-[0.2em]">II. Pengeluaran & Operasional</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-xl transition-all hover:border-yellow-500/50">
                                        <p class="text-[10px] font-black text-yellow-600 dark:text-yellow-400 uppercase tracking-widest mb-2">Sisa Petty Cash</p>
                                        <p class="text-2xl font-black text-slate-900 dark:text-white leading-tight">Rp {{ number_format($this->pettyCashBalance, 0, ',', '.') }}</p>
                                    </div>

                                    <div class="bg-red-500 p-6 rounded-[2rem] shadow-lg shadow-red-500/20 relative overflow-hidden group">
                                        <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition-all"></div>
                                        <p class="text-[10px] font-black text-red-100 uppercase tracking-widest opacity-80 mb-2">Total Keluar Hari Ini</p>
                                        <p class="text-2xl font-black text-white leading-tight">Rp {{ number_format($this->todayExpenses->sum('amount'), 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-white/5 overflow-hidden mb-12">
                                <div class="p-8 border-b dark:border-white/5 flex justify-between items-center">
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Rincian Pengeluaran</h3>
                                    <span class="text-[10px] font-black bg-slate-100 dark:bg-white/5 px-4 py-2 rounded-full uppercase text-slate-500 tracking-widest">Live Report</span>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                                        <thead class="text-[10px] text-slate-400 uppercase bg-slate-50 dark:bg-zinc-800/50 dark:text-zinc-500 font-black tracking-[0.15em]">
                                            <tr>
                                                <th class="px-8 py-5">Jam Transaksi</th>
                                                <th class="px-8 py-5">Sumber Dana</th>
                                                <th class="px-8 py-5">Keperluan & Deskripsi</th>
                                                <th class="px-8 py-5 text-right">Nominal (IDR)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                            @forelse($this->todayExpenses as $expense)
                                                <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group">
                                                    <td class="px-8 py-5 font-bold text-xs">{{ $expense->created_at->format('H:i') }}</td>
                                                    <td class="px-8 py-5">
                                                        <span class="px-3 py-1 bg-slate-100 dark:bg-white/5 rounded-lg text-[9px] font-black uppercase text-slate-600 dark:text-zinc-400">
                                                            {{ $expense->wallet->name ?? '-' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-8 py-5">
                                                        <p class="font-bold text-slate-900 dark:text-zinc-200 whitespace-normal max-w-xs">{{ $expense->name }}</p>
                                                    </td>
                                                    <td class="px-8 py-5 text-right font-black text-red-500 italic">
                                                        - {{ number_format($expense->amount, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-8 py-16 text-center">
                                                        <div class="opacity-20 flex flex-col items-center">
                                                            <x-heroicon-o-document-text class="w-12 h-12 mb-3" />
                                                            <p class="text-xs font-black uppercase tracking-widest">Belum Ada Data Pengeluaran</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-slate-900 to-black dark:from-zinc-900 dark:to-black p-8 md:p-12 rounded-[3rem] shadow-2xl relative overflow-hidden group">
                                <div class="absolute right-0 top-0 w-64 h-64 bg-primary/10 rounded-full blur-3xl opacity-50 group-hover:scale-125 transition-transform"></div>
                                
                                <div class="flex flex-col md:flex-row items-center justify-between gap-8 relative z-10">
                                    <div class="text-center md:text-left">
                                        <h3 class="text-2xl md:text-3xl font-black text-white uppercase tracking-tight">Tutup Buku & Selesai</h3>
                                        <p class="text-slate-400 text-sm mt-2">Pastikan semua transaksi setor tunai fisik sudah diverifikasi sebelum mengunci kasir.</p>
                                    </div>
                                    
                                    <button wire:click="closeRegister" 
                                            class="w-full md:w-auto px-10 py-5 bg-primary text-white font-black rounded-[2rem] hover:bg-orange-600 shadow-2xl shadow-primary/30 flex items-center justify-center gap-4 transition-all active:scale-95 group/btn">
                                        <span class="tracking-widest uppercase text-sm">Tutup Kasir & Print</span>
                                        <x-heroicon-s-lock-closed class="w-6 h-6 transition-transform group-hover/btn:rotate-12" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>

                    <nav class="md:hidden fixed bottom-6 left-4 right-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-lg border border-slate-200 dark:border-white/5 flex justify-around items-center py-3 z-40 rounded-3xl shadow-[0_10px_30px_-10px_rgba(0,0,0,0.2)]"
                        style="
                            background-image: url('/images/ornaments/arabesque.png');
                            background-repeat: repeat;
                            background-size: 150px 150px;
                        ">
                
                        <button wire:click="setTab('payment')" 
                                class="relative flex flex-col items-center gap-1 w-20 transition-all duration-300 {{ $activeTab === 'payment' ? 'text-primary scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                            @if($activeTab === 'payment')
                                <div class="absolute -top-3 w-1 h-1 bg-primary rounded-full shadow-[0_0_8px_#FF7A00]"></div>
                            @endif
                            <x-heroicon-s-banknotes class="w-6 h-6" />
                            <span class="text-[10px] font-black uppercase tracking-tighter">Payment</span>
                        </button>

                        <button wire:click="setTab('expense')" 
                                class="relative flex flex-col items-center gap-1 w-20 transition-all duration-300 {{ $activeTab === 'expense' ? 'text-red-500 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                            @if($activeTab === 'expense')
                                <div class="absolute -top-3 w-1 h-1 bg-red-500 rounded-full shadow-[0_0_8px_#EF4444]"></div>
                            @endif
                            <x-heroicon-s-receipt-refund class="w-6 h-6" />
                            <span class="text-[10px] font-black uppercase tracking-tighter">Expense</span>
                        </button>

                        <button wire:click="setTab('report')" 
                                class="relative flex flex-col items-center gap-1 w-20 transition-all duration-300 {{ $activeTab === 'report' ? 'text-purple-500 scale-110' : 'text-slate-400 dark:text-zinc-500' }}">
                            @if($activeTab === 'report')
                                <div class="absolute -top-3 w-1 h-1 bg-purple-500 rounded-full shadow-[0_0_8px_#A855F7]"></div>
                            @endif
                            <x-heroicon-s-chart-bar class="w-6 h-6" />
                            <span class="text-[10px] font-black uppercase tracking-tighter">Laporan</span>
                        </button>

                    </nav>

                    @if($showConfirmModal)
                    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                        <div x-on:click="$wire.set('showConfirmModal', false)" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                        
                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-md p-8 relative z-10 animate-fade-in-up border border-white/10">
                            <div class="text-center">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl bg-emerald-500/10 text-emerald-500 mb-6">
                                    <x-heroicon-s-check-badge class="h-10 w-10" />
                                </div>
                                
                                <h3 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Terima Pembayaran?</h3>
                                <p class="text-slate-500 dark:text-zinc-400 mt-2 text-sm uppercase font-bold tracking-widest">Konfirmasi Transaksi Masuk</p>
                                
                                <div class="mt-8 p-6 bg-slate-50 dark:bg-white/5 rounded-3xl border border-slate-100 dark:border-white/5">
                                    <p class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-[0.2em] mb-1">Nominal Diterima</p>
                                    <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400">
                                        <span class="text-lg opacity-50">Rp</span> {{ number_format($cleanAmount, 0, ',', '.') }}
                                    </p>
                                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-white/5 flex justify-between items-center">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Metode</span>
                                        <span class="px-3 py-1 bg-emerald-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-lg shadow-emerald-500/20">
                                            {{ $paymentMethod }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 grid grid-cols-2 gap-4">
                                <button wire:click="$set('showConfirmModal', false)" 
                                        class="px-6 py-4 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-zinc-400 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-200 transition-all">
                                    Batal
                                </button>
                                <button wire:click="processPayment" 
                                        class="px-6 py-4 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-500 shadow-xl shadow-emerald-500/20 transition-all active:scale-95">
                                    Ya, Terima ✅
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($showExpenseModal)
                    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                        <div x-on:click="$wire.set('showExpenseModal', false)" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                        
                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-md p-8 relative z-10 animate-fade-in-up border-t-[6px] border-red-500">
                            <div class="text-center">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl bg-red-500/10 text-red-500 mb-6">
                                    <x-heroicon-s-exclamation-triangle class="h-10 w-10" />
                                </div>
                                
                                <h3 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Konfirmasi Keluar</h3>
                                <p class="text-slate-500 dark:text-zinc-400 mt-2 text-sm uppercase font-bold tracking-widest">Data akan dicatat sebagai HPP/Ops</p>

                                <div class="mt-8 p-6 bg-red-500/5 rounded-3xl border border-red-100 dark:border-red-500/10 text-center">
                                    <p class="text-[10px] font-black text-red-400 uppercase tracking-[0.2em] mb-1">Total Pengeluaran</p>
                                    <p class="text-3xl font-black text-red-600 dark:text-red-400">
                                        <span class="text-lg opacity-50">Rp</span> {{ number_format($cleanAmount, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-8 grid grid-cols-2 gap-4">
                                <button wire:click="$set('showExpenseModal', false)" 
                                        class="px-6 py-4 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-zinc-400 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-200 transition-all">
                                    Batal
                                </button>
                                <button wire:click="saveExpense" 
                                        class="px-6 py-4 bg-red-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-red-500 shadow-xl shadow-red-500/20 transition-all active:scale-95">
                                    Konfirmasi 💸
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($showDepositModal)
                    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                        <div x-on:click="$wire.set('showDepositModal', false)" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                        
                        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-md p-8 relative z-10 animate-fade-in-up border-t-[6px] border-blue-600">
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl bg-blue-500/10 text-blue-600 mb-6">
                                <x-heroicon-s-arrow-path-rounded-square class="h-10 w-10" />
                            </div>

                            <h3 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white mb-2 text-center uppercase tracking-tight">Setor Tunai (Handover)</h3>
                            <p class="text-center text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-8">Pemindahan Fisik Uang ke Bank</p>
                            
                            <div class="space-y-5">
                                <div class="bg-blue-500/5 dark:bg-blue-500/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-500/20">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Sumber Dana</span>
                                        <span class="text-[10px] font-black text-slate-800 dark:text-white uppercase">Laci Kasir</span>
                                    </div>
                                    <p class="text-lg font-black text-blue-600 mt-1">Rp {{ number_format($this->totalBalance, 0, ',', '.') }}</p>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-2 ml-1">
                                        Target Rekening Bank
                                    </label>
                                    
                                    <div class="relative group">
                                        <select wire:model="depositTargetBankId" 
                                                class="w-full p-4 pl-12 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-bold text-sm text-slate-700 dark:text-zinc-200 focus:border-blue-500/50 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all appearance-none cursor-pointer">
                                            
                                            <option value="" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">-- Pilih Bank --</option>

                                            @foreach($this->bankWallets as $bank)
                                                <option value="{{ $bank->id }}" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                    {{ $bank->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <x-heroicon-s-building-library class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 group-focus-within:text-blue-500 transition-colors" />

                                        <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Nominal Disetor</label>
                                    <div class="relative group">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-blue-300">Rp</div>
                                        <input wire:model="depositAmountRaw" type="text" x-on:input="$el.value = formatRupiah($el.value)" 
                                            class="w-full p-4 pl-12 pr-6 border-2 border-blue-500 bg-blue-500/5 rounded-2xl text-xl font-black text-right focus:ring-4 focus:ring-blue-500/10 transition-all outline-none dark:text-white" 
                                            placeholder="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-10 grid grid-cols-2 gap-4">
                                <button wire:click="$set('showDepositModal', false)" 
                                        class="px-6 py-4 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-zinc-400 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">
                                    Batal
                                </button>
                                <button wire:click="processDeposit" 
                                        class="px-6 py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-500 shadow-xl shadow-blue-500/20 transition-all active:scale-95">
                                    Proses Setor 
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($showMediaModal)
                    <div class="fixed inset-0 z-[100] flex items-end md:items-center justify-center sm:p-6" x-transition.opacity>
                        
                        <div wire:click="$set('showMediaModal', false)" 
                            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

                        <div class="relative w-full max-w-lg bg-white dark:bg-zinc-900 rounded-t-[2.5rem] md:rounded-[2.5rem] shadow-2xl flex flex-col max-h-[90vh] overflow-hidden animate-slide-up md:animate-fade-in-up border border-white/10">
                            
                            <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center shrink-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-md z-10">
                                <div>
                                    <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2 uppercase tracking-tight">
                                        <div class="p-2 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl text-indigo-600 dark:text-indigo-400">
                                            <x-heroicon-s-swatch class="w-5 h-5" />
                                        </div>
                                        Creative Support
                                    </h3>
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest mt-1 ml-11">Upload Aset & Request Desain</p>
                                </div>
                                <button wire:click="$set('showMediaModal', false)" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                                    <x-heroicon-s-x-mark class="w-6 h-6" />
                                </button>
                            </div>

                            <div class="px-6 pt-6 shrink-0">
                                <div class="flex p-1.5 bg-slate-100 dark:bg-black/20 rounded-2xl border border-slate-200 dark:border-white/5">
                                    <button wire:click="$set('mediaTab', 'upload')" 
                                        class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex justify-center items-center gap-2 
                                        {{ $mediaTab === 'upload' ? 'bg-white dark:bg-zinc-800 shadow-md text-indigo-600 dark:text-indigo-400 scale-[1.02]' : 'text-slate-500 hover:text-slate-700 dark:text-zinc-500' }}">
                                        <x-heroicon-s-arrow-up-tray class="w-4 h-4" /> Upload Aset
                                    </button>
                                    <button wire:click="$set('mediaTab', 'request')" 
                                        class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex justify-center items-center gap-2 
                                        {{ $mediaTab === 'request' ? 'bg-white dark:bg-zinc-800 shadow-md text-indigo-600 dark:text-indigo-400 scale-[1.02]' : 'text-slate-500 hover:text-slate-700 dark:text-zinc-500' }}">
                                        <x-heroicon-s-pencil-square class="w-4 h-4" /> Request Desain
                                    </button>
                                </div>
                            </div>

                            <div class="p-6 overflow-y-auto custom-scrollbar">
                                
                                @if($mediaTab === 'upload')
                                <div class="space-y-6 animate-fade-in">
                                    
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-2 block ml-1">
                                            Penyimpanan Target
                                        </label>
                                        <div class="relative group">
                                            <select wire:model.live="selectedPackageId" 
                                                    class="w-full p-4 pl-12 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-bold text-sm text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all appearance-none cursor-pointer">
                                                
                                                <option value="" class="text-slate-700 dark:text-slate-300 dark:bg-zinc-800">
                                                    📂 Folder Umum / Non-Grup
                                                </option>
                                                
                                                @foreach($this->packages as $pkg)
                                                    <option value="{{ $pkg->id }}" class="text-slate-700 dark:text-slate-300 dark:bg-zinc-800">
                                                        ✈️ {{ \Illuminate\Support\Str::limit($pkg->name, 40) }}
                                                    </option>
                                                @endforeach

                                            </select>
                                            
                                            <x-heroicon-s-folder-open class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 group-focus-within:text-indigo-500 transition-colors" />
                                            <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                                        </div>
                                    </div>

                                    <div class="border-3 border-dashed border-slate-200 dark:border-zinc-700 rounded-[2rem] p-8 text-center hover:border-indigo-400 hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition-all relative group cursor-pointer">
                                        <input type="file" wire:model="mediaPhotos" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        
                                        <div class="space-y-4 pointer-events-none">
                                            <div wire:loading wire:target="mediaPhotos" class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm z-20 rounded-[2rem]">
                                                <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-3"></div>
                                                <p class="text-xs font-black text-indigo-600 uppercase tracking-widest animate-pulse">Mengupload...</p>
                                            </div>

                                            <div wire:loading.remove wire:target="mediaPhotos">
                                                <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-3xl bg-indigo-50 dark:bg-indigo-500/10 mb-4 group-hover:scale-110 transition-transform duration-300 shadow-sm">
                                                    <x-heroicon-s-camera class="w-8 h-8 text-indigo-500" />
                                                </div>
                                                <h4 class="text-base font-black text-slate-700 dark:text-white group-hover:text-indigo-600 transition-colors">
                                                    Tap untuk Upload Foto
                                                </h4>
                                                <p class="text-xs text-slate-400 dark:text-zinc-500 mt-1 font-medium">
                                                    Bisa pilih banyak file sekaligus (JPG, PNG)
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-2 block ml-1">
                                            Label / Tag (Opsional)
                                        </label>
                                        <div class="relative group">
                                            <input wire:model="mediaTags" type="text" placeholder="Contoh: Bukti Transfer, Paspor Jamaah..." 
                                                class="w-full p-4 pl-12 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-medium text-sm text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-400">
                                            <x-heroicon-s-tag class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 group-focus-within:text-indigo-500 transition-colors" />
                                        </div>
                                    </div>

                                    <button wire:click="saveMediaAssets" wire:loading.attr="disabled" 
                                        class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-black py-4 rounded-2xl shadow-xl shadow-indigo-500/20 flex justify-center items-center gap-3 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span wire:loading.remove wire:target="saveMediaAssets" class="flex items-center gap-2">
                                            <x-heroicon-s-cloud-arrow-up class="w-5 h-5" />
                                            SIMPAN FILE KE CLOUD
                                        </span>
                                        <span wire:loading wire:target="saveMediaAssets" class="flex items-center gap-2">
                                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            MENYIMPAN...
                                        </span>
                                    </button>
                                </div>
                                @endif

                                @if($mediaTab === 'request')
                                <div class="space-y-5 animate-fade-in">
                                    
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-2 block ml-1">Judul Request</label>
                                        <input wire:model="reqTitle" type="text" placeholder="Ct: Flyer Promo Ramadhan" 
                                            class="w-full p-4 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-bold text-sm text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all">
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-2 block ml-1">Detail Kebutuhan</label>
                                        <textarea wire:model="reqDesc" rows="4" placeholder="Jelaskan detail warna, teks, ukuran, atau referensi..." 
                                            class="w-full p-4 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-medium text-sm text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all"></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-2 block ml-1">Deadline</label>
                                            <div class="relative group">
                                                <input wire:model="reqDeadline" type="date" 
                                                    class="w-full p-4 pl-10 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-bold text-sm text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all">
                                                <x-heroicon-s-calendar class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase tracking-widest mb-2 block ml-1">Prioritas</label>
                                            <div class="relative group">
                                                <select wire:model="reqPriority" 
                                                        class="w-full p-4 pl-10 bg-slate-50 dark:bg-white/5 border-2 border-slate-100 dark:border-white/5 rounded-2xl font-bold text-sm text-slate-700 dark:text-zinc-200 focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all appearance-none cursor-pointer">
                                                    
                                                    <option value="low" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                        ☕ Low (Santai)
                                                    </option>
                                                    <option value="medium" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                        ⚡ Medium (Standar)
                                                    </option>
                                                    <option value="high" class="text-slate-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                        🔥 Urgent (Penting)
                                                    </option>

                                                </select>
                                                
                                                <x-heroicon-s-flag class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 group-focus-within:text-indigo-500 transition-colors" />
                                                
                                                <x-heroicon-s-chevron-down class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                                            </div>
                                        </div>
                                    </div>

                                    <button wire:click="saveContentRequest" wire:loading.attr="disabled"
                                        class="w-full bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-zinc-200 text-white dark:text-black font-black py-4 rounded-2xl shadow-xl mt-4 flex justify-center items-center gap-3 transition transform active:scale-95 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="saveContentRequest" class="flex items-center gap-2">
                                            <x-heroicon-s-paper-airplane class="w-5 h-5" />
                                            KIRIM REQUEST
                                        </span>
                                        <span wire:loading wire:target="saveContentRequest" class="flex items-center gap-2">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            MENGIRIM...
                                        </span>
                                    </button>
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>
                    @endif

                    <div x-show="$wire.showProofModal" 
                        class="fixed inset-0 z-[100] flex items-center justify-center p-4" 
                        style="display: none;" 
                        x-transition.opacity>
                        
                        <div wire:click="$set('showProofModal', false)" 
                            class="fixed inset-0 bg-slate-950/80 backdrop-blur-md cursor-pointer transition-opacity"></div>

                        <div class="relative bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up border border-white/10" 
                            x-transition.scale>
                            
                            <div class="flex justify-between items-center px-6 py-5 border-b border-slate-100 dark:border-white/5 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm z-10">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-500 dark:text-zinc-300">
                                        <x-heroicon-s-document-magnifying-glass class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h3 class="font-black text-lg text-slate-900 dark:text-white uppercase tracking-tight">Bukti Transfer</h3>
                                        <p class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Lampiran Transaksi</p>
                                    </div>
                                </div>
                                
                                <button wire:click="$set('showProofModal', false)" 
                                        class="w-10 h-10 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 transition-all transform hover:rotate-90">
                                    <x-heroicon-s-x-mark class="w-6 h-6" />
                                </button>
                            </div>

                            <div class="flex-1 bg-slate-100 dark:bg-black/50 flex items-center justify-center overflow-auto relative p-4 custom-scrollbar">
                                
                                @if($proofType === 'image')
                                    <div class="relative group shadow-2xl rounded-lg overflow-hidden">
                                        <img src="{{ $proofUrl }}" 
                                            class="max-w-full max-h-[65vh] object-contain transform transition-transform duration-500 group-hover:scale-[1.02]" 
                                            alt="Bukti Transfer">
                                        
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors pointer-events-none"></div>
                                    </div>
                                @else
                                    <div class="text-center py-12 px-6 bg-white dark:bg-zinc-800 rounded-[2rem] shadow-xl border border-slate-200 dark:border-white/5 max-w-sm w-full">
                                        <div class="w-24 h-24 mx-auto bg-primary/10 rounded-full flex items-center justify-center mb-6 animate-pulse">
                                            <x-heroicon-s-document-text class="w-12 h-12 text-primary" />
                                        </div>
                                        
                                        <h4 class="text-lg font-black text-slate-900 dark:text-white mb-2">Dokumen Terlampir</h4>
                                        <p class="text-sm text-slate-500 dark:text-zinc-400 mb-8 font-medium">
                                            File ini bukan gambar preview (PDF/DOCX). Silakan unduh untuk melihat isinya.
                                        </p>
                                        
                                        <a href="{{ $proofUrl }}" target="_blank" 
                                        class="inline-flex items-center gap-2 px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-black rounded-2xl font-black text-xs uppercase tracking-widest hover:opacity-90 transition-all shadow-lg">
                                            <x-heroicon-s-eye class="w-4 h-4" />
                                            Buka Dokumen
                                        </a>
                                    </div>
                                @endif

                            </div>

                            <div class="px-6 py-5 border-t border-slate-100 dark:border-white/5 bg-white dark:bg-zinc-900 flex justify-between items-center gap-4">
                                
                                <a href="{{ $proofUrl }}" download 
                                class="flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-zinc-300 font-bold text-xs uppercase tracking-widest hover:bg-primary hover:text-white transition-all group">
                                    <x-heroicon-s-arrow-down-tray class="w-4 h-4 group-hover:animate-bounce" />
                                    <span>Download File</span>
                                </a>

                                <button wire:click="$set('showProofModal', false)" 
                                        class="px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-black rounded-xl font-black text-xs uppercase tracking-widest hover:opacity-80 transition-all shadow-lg">
                                    Tutup Viewer
                                </button>
                            </div>

                        </div>
                    </div>

                </main>
            </div>
        </div>
    </main>
</div>

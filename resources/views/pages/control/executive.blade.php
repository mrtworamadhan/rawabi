<div class="flex w-full h-full relative bg-gray-50 dark:bg-zinc-950">

    <aside class="hidden md:flex w-24 bg-white dark:bg-zinc-900 border-r border-gray-200 dark:border-white/10 flex-col items-center py-6 gap-4 z-20 shadow-sm shrink-0">
        <button wire:click="$set('activeTab', 'analytics')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'analytics'
    ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-400/10 dark:text-indigo-400 font-bold ring-1 ring-indigo-500/20 shadow-sm'
    : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            <x-heroicon-o-chart-bar-square class="w-7 h-7 transition-transform group-hover:scale-110" />
            <span class="text-[9px] uppercase font-bold tracking-wide">Analisa</span>
        </button>

        <button wire:click="$set('activeTab', 'batch_report')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'batch_report'
    ? 'text-purple-600 bg-purple-50 dark:bg-purple-400/10 dark:text-purple-400 font-bold ring-1 ring-purple-500/20 shadow-sm'
    : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            <x-heroicon-o-cube class="w-7 h-7 transition-transform group-hover:scale-110" />
            <span class="text-[9px] uppercase font-bold tracking-wide">Batch</span>
        </button>
        
        <button wire:click="$set('activeTab', 'finance')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'finance'
    ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-400/10 dark:text-emerald-400 font-bold ring-1 ring-emerald-500/20 shadow-sm'
    : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            <x-heroicon-o-banknotes class="w-7 h-7 transition-transform group-hover:scale-110" />
            <span class="text-[9px] uppercase font-bold tracking-wide">Finance</span>
        </button>

        <button wire:click="$set('activeTab', 'marketing')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'marketing'
    ? 'text-blue-600 bg-blue-50 dark:bg-blue-400/10 dark:text-blue-400 font-bold ring-1 ring-blue-500/20 shadow-sm'
    : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            <x-heroicon-o-presentation-chart-line class="w-7 h-7 transition-transform group-hover:scale-110" />
            <span class="text-[9px] uppercase font-bold tracking-wide">Marketing</span>
        </button>

        <button wire:click="$set('activeTab', 'media')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'media'
    ? 'text-pink-600 bg-pink-50 dark:bg-pink-400/10 dark:text-pink-400 font-bold ring-1 ring-pink-500/20 shadow-sm'
    : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            <x-heroicon-o-swatch class="w-7 h-7 transition-transform group-hover:scale-110" />
            <span class="text-[9px] uppercase font-bold tracking-wide">Media</span>
        </button>

        <button wire:click="$set('activeTab', 'hr')"
            class="group flex flex-col items-center gap-1 p-3 rounded-xl transition w-16 h-16 justify-center relative
            {{ $activeTab === 'hr'
    ? 'text-orange-600 bg-orange-50 dark:bg-orange-400/10 dark:text-orange-400 font-bold ring-1 ring-orange-500/20 shadow-sm'
    : 'text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 hover:bg-gray-50 dark:hover:bg-white/5'
            }}">
            <x-heroicon-o-users class="w-7 h-7 transition-transform group-hover:scale-110" />
            <span class="text-[9px] uppercase font-bold tracking-wide">SDM</span>
        </button>

    </aside>
    <div x-show="mobileMenuOpen" 
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-gray-900/80 z-50 md:hidden backdrop-blur-sm"></div>

    <div x-show="mobileMenuOpen"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-[280px] bg-white dark:bg-zinc-900 shadow-2xl flex flex-col border-r border-gray-200 dark:border-white/10 md:hidden">
        
        <div class="flex justify-between items-center p-6 border-b border-gray-100 dark:border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-black dark:bg-white rounded-lg flex items-center justify-center text-white dark:text-black font-black text-sm">
                    R
                </div>
                <span class="font-bold text-lg text-gray-900 dark:text-white tracking-tight">Navigasi</span>
            </div>
            <button @click="mobileMenuOpen = false" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-white/10 text-gray-400 transition">
                <x-heroicon-m-x-mark class="w-6 h-6" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto no-scrollbar p-4 flex flex-col gap-2">
            
            {{-- ANALYTICS --}}
            <button wire:click="$set('activeTab', 'analytics'); mobileMenuOpen = false"
                class="flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all font-bold text-sm w-full text-left
                {{ $activeTab === 'analytics'
                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 ring-1 ring-indigo-500/20 shadow-sm'
                    : 'text-gray-500 hover:bg-gray-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                <x-heroicon-o-chart-bar-square class="w-6 h-6" />
                <span>Business Analytics</span>
            </button>

            {{-- BATCH REPORT --}}
            <button wire:click="$set('activeTab', 'batch_report'); mobileMenuOpen = false"
                class="flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all font-bold text-sm w-full text-left
                {{ $activeTab === 'batch_report'
                    ? 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400 ring-1 ring-purple-500/20 shadow-sm'
                    : 'text-gray-500 hover:bg-gray-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                <x-heroicon-o-cube class="w-6 h-6" />
                <span>Batch Report</span>
            </button>

            {{-- FINANCE --}}
            <button wire:click="$set('activeTab', 'finance'); mobileMenuOpen = false"
                class="flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all font-bold text-sm w-full text-left
                {{ $activeTab === 'finance'
                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 ring-1 ring-emerald-500/20 shadow-sm'
                    : 'text-gray-500 hover:bg-gray-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                <x-heroicon-o-banknotes class="w-6 h-6" />
                <span>Financial Report</span>
            </button>

            {{-- MARKETING --}}
            <button wire:click="$set('activeTab', 'marketing'); mobileMenuOpen = false"
                class="flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all font-bold text-sm w-full text-left
                {{ $activeTab === 'marketing'
                    ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 ring-1 ring-blue-500/20 shadow-sm'
                    : 'text-gray-500 hover:bg-gray-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                <x-heroicon-o-presentation-chart-line class="w-6 h-6" />
                <span>Marketing & Sales</span>
            </button>

            {{-- MEDIA --}}
            <button wire:click="$set('activeTab', 'media'); mobileMenuOpen = false"
                class="flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all font-bold text-sm w-full text-left
                {{ $activeTab === 'media'
                    ? 'bg-pink-50 text-pink-700 dark:bg-pink-500/10 dark:text-pink-400 ring-1 ring-pink-500/20 shadow-sm'
                    : 'text-gray-500 hover:bg-gray-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                <x-heroicon-o-swatch class="w-6 h-6" />
                <span>Media & Content</span>
            </button>

            {{-- HR / SDM --}}
            <button wire:click="$set('activeTab', 'hr'); mobileMenuOpen = false"
                class="flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all font-bold text-sm w-full text-left
                {{ $activeTab === 'hr'
                    ? 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 ring-1 ring-orange-500/20 shadow-sm'
                    : 'text-gray-500 hover:bg-gray-50 dark:text-zinc-400 dark:hover:bg-white/5' }}">
                <x-heroicon-o-users class="w-6 h-6" />
                <span>Human Resources</span>
            </button>

        </div>
        
        <div class="p-6 border-t border-gray-100 dark:border-white/5">
            <p class="text-xs text-center text-gray-400">
                Rawabi System v2.0 <br>
                <span class="opacity-50">© {{ date('Y') }} All Rights Reserved</span>
            </p>
        </div>
    </div>

    <div class="flex-1 h-full overflow-y-auto custom-scrollbar p-4 md:p-8 pb-24 md:pb-8">
        
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                    @if($activeTab === 'finance') 
                        <span class="bg-emerald-100 text-emerald-600 p-2 rounded-lg"><x-heroicon-m-banknotes class="w-6 h-6"/></span> Laporan Keuangan
                    @elseif($activeTab === 'marketing') 
                        <span class="bg-blue-100 text-blue-600 p-2 rounded-lg"><x-heroicon-m-presentation-chart-line class="w-6 h-6"/></span> Performa Marketing
                    @elseif($activeTab === 'media') 
                        <span class="bg-pink-100 text-pink-600 p-2 rounded-lg"><x-heroicon-m-swatch class="w-6 h-6"/></span> Aktivitas Media
                    @elseif($activeTab === 'hr') 
                        <span class="bg-orange-100 text-orange-600 p-2 rounded-lg"><x-heroicon-m-users class="w-6 h-6"/></span> Karyawan & KPI
                    @endif
                </h1>
                <p class="text-sm text-gray-500 dark:text-zinc-400 mt-2 ml-1">
                    Executive Overview per <b>{{ now()->format('d F Y') }}</b>
                </p>
            </div>
            
            <div class="flex items-center gap-2 text-xs font-bold bg-white dark:bg-zinc-900 px-3 py-1.5 rounded-full border border-gray-200 dark:border-white/10 shadow-sm">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span class="text-gray-600 dark:text-zinc-300">Live Data</span>
            </div>
        </div>

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
                                    chart: { type: 'area', height: 350, toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
                                    colors: ['#10b981', '#f59e0b', '#ef4444'], // Emerald, Amber, Red
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

                    <div class="bg-gradient-to-br from-indigo-600 to-violet-700 p-8 rounded-[2.5rem] text-white shadow-2xl shadow-indigo-500/30 relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-2 opacity-80">
                                <x-heroicon-s-banknotes class="w-5 h-5" />
                                <p class="font-black text-xs uppercase tracking-[0.2em]">Net Profit</p>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-black tracking-tight">
                                <span class="text-lg opacity-60 mr-1">Rp</span>{{ number_format($this->analyticsStats['summary']['total_profit'] / 1000000, 1) }}<span class="text-lg opacity-60 ml-1">M</span>
                            </h2>
                            <p class="text-[10px] font-bold mt-2 py-1 px-3 bg-white/10 rounded-lg inline-block backdrop-blur-sm border border-white/10">
                                Net = Income - (HPP + Ops)
                            </p>
                        </div>
                        <x-heroicon-s-chart-pie class="absolute -right-6 -bottom-6 w-40 h-40 text-white/10 group-hover:scale-110 transition-transform duration-700 rotate-12" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white dark:bg-zinc-900 p-5 rounded-[2rem] border border-slate-100 dark:border-white/5 shadow-sm group hover:border-emerald-200 transition-colors">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Income</p>
                            <p class="text-lg font-black text-emerald-600 group-hover:scale-105 transition-transform origin-left">
                                {{ number_format($this->analyticsStats['summary']['total_income'] / 1000000, 0) }} M
                            </p>
                        </div>
                        <div class="bg-white dark:bg-zinc-900 p-5 rounded-[2rem] border border-slate-100 dark:border-white/5 shadow-sm group hover:border-red-200 transition-colors">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Expense</p>
                            <p class="text-lg font-black text-red-500 group-hover:scale-105 transition-transform origin-left">
                                {{ number_format($this->analyticsStats['summary']['total_expense'] / 1000000, 0) }} M
                            </p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] border border-slate-100 dark:border-white/5 flex-1 flex flex-col items-center justify-center relative overflow-hidden"
                        x-data="{ ... }" x-init="init()"> <div class="w-full flex justify-between items-center mb-2 px-2">
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
                    x-data="{ init() { ... } }" x-init="init()"> <div class="mb-6 px-2">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Marketing Trend</h3>
                        <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Leads vs Closing Performance</p>
                    </div>
                    <div x-ref="chart" class="w-full h-72"></div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 flex flex-col justify-between relative overflow-hidden"
                    x-data="{ ... }" x-init="init()"> <div class="relative z-10 px-2">
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
                
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5"
                    x-data="{ ... }" x-init="init()">
                    <div class="mb-2 px-2">
                        <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Lead Source Quality</h3>
                        <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Mana yang menghasilkan closing?</p>
                    </div>
                    <div x-ref="chart" class="w-full h-80"></div>
                </div>

                <div class="space-y-6">
                    
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5"
                        x-data="{ ... }" x-init="init()">
                        <div class="flex justify-between items-start mb-4 px-2">
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Growth Jamaah</h3>
                                <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Total Pax Monthly</p>
                            </div>
                            <div class="text-right">
                                <span class="block text-xl font-black text-indigo-600 dark:text-indigo-400">{{ $this->analyticsStats['summary']['total_jamaah'] }} Pax</span>
                                <span class="text-[10px] font-black {{ $this->analyticsStats['summary']['jamaah_growth'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                                    {{ $this->analyticsStats['summary']['jamaah_growth'] >= 0 ? '+' : '' }}{{ $this->analyticsStats['summary']['jamaah_growth'] }}% YoY
                                </span>
                            </div>
                        </div>
                        <div x-ref="chart" class="w-full h-60"></div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5"
                        x-data="{ ... }" x-init="init()">
                        <div class="mb-2 px-2">
                            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Team Productivity</h3>
                            <p class="text-xs font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Task Completion Rate</p>
                        </div>
                        <div x-ref="chart" class="w-full h-48"></div>
                    </div>

                </div>
            </div>

        </div>
        @endif

        @if($activeTab === 'batch_report')
        <div class="animate-fade-in space-y-6">
            
            <div class="flex justify-between items-center bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-white/5">
                <div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-m-cube class="w-6 h-6 text-purple-600"/> Laporan Keberangkatan
                    </h2>
                    <p class="text-xs text-gray-500">Monitoring status booking, keuangan, dan operasional per batch.</p>
                </div>
                <div class="w-64">
                    <select wire:model.live="selectedBatchId" class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-lg text-sm font-bold p-2.5 dark:text-white focus:ring-purple-500">
                        <option value="">-- Pilih Batch --</option>
                        @foreach(UmrahPackage::latest()->take(10)->get() as $pkg)
                            <option value="{{ $pkg->id }}">{{ $pkg->name }} ({{ Carbon::parse($pkg->departure_date)->format('d M') }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($this->batchReportData)
            
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5">
                <div class="flex justify-between items-end mb-2">
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Keterisian Kursi (Seat Utilization)</p>
                        <div class="flex items-baseline gap-2">
                            <h2 class="text-3xl font-black text-gray-900 dark:text-white">
                                {{ $this->batchReportData['seats']['booked'] }} 
                                <span class="text-lg text-gray-400 font-medium">/ {{ $this->batchReportData['seats']['total'] }} Pax</span>
                            </h2>
                            
                            @if($this->batchReportData['seats']['status'] === 'full')
                                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-bold uppercase">Full Booked</span>
                            @elseif($this->batchReportData['seats']['status'] === 'warning')
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs font-bold uppercase">Hampir Penuh</span>
                            @else
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-bold uppercase">Open Seat</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-400">Sisa Kuota</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ $this->batchReportData['seats']['available'] }}</p>
                    </div>
                </div>

                <div class="w-full bg-gray-100 dark:bg-zinc-800 h-4 rounded-full overflow-hidden relative">
                    @php
        $pct = $this->batchReportData['seats']['percent'];
        $color = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-yellow-500' : 'bg-emerald-500');
                    @endphp
                    <div class="{{ $color }} h-full transition-all duration-1000 ease-out flex items-center justify-end pr-2" style="width: {{ $pct }}%">
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-2 text-right">{{ number_format($pct, 0) }}% Terisi</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-5 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">Total Omset</p>
                    <h3 class="text-2xl font-black text-indigo-900 dark:text-white">Rp {{ number_format($this->batchReportData['finance']['omset'] / 1000000, 1) }} Jt</h3>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-5 rounded-xl border border-emerald-100 dark:border-emerald-800/30">
                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase">Cash Masuk (Paid)</p>
                    <h3 class="text-2xl font-black text-emerald-900 dark:text-white">Rp {{ number_format($this->batchReportData['finance']['paid'] / 1000000, 1) }} Jt</h3>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-5 rounded-xl border border-red-100 dark:border-red-800/30 flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase">Sisa Pelunasan (AR)</p>
                        <h3 class="text-2xl font-black text-red-600 dark:text-red-400">Rp {{ number_format($this->batchReportData['finance']['arrears'] / 1000000, 1) }} Jt</h3>
                    </div>
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-300" />
                </div>
            </div>

            <div class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 relative h-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <x-heroicon-m-ticket class="w-5 h-5 text-blue-500" /> Jadwal Penerbangan
                            </h3>
                            <button wire:click="exportFlightPdf" class="flex items-center gap-1 text-[10px] font-bold text-white bg-blue-600 px-3 py-1.5 rounded hover:bg-blue-700 transition shadow">
                                <x-heroicon-m-arrow-down-tray class="w-3 h-3" /> PDF
                            </button>
                        </div>
                        
                        <div class="space-y-4 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                            @forelse($this->batchReportData['flights'] as $flight)
                            <div class="flex gap-4 border-l-2 border-blue-200 dark:border-blue-800 pl-4">
                                <div class="w-12 text-center shrink-0">
                                    <span class="block text-xl font-bold text-gray-800 dark:text-white">{{ Carbon::parse($flight->depart_at)->format('H:i') }}</span>
                                    <span class="text-[10px] text-gray-400">{{ Carbon::parse($flight->depart_at)->format('d M') }}</span>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $flight->airline }} ({{ $flight->flight_number }})</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $flight->depart_airport }} <span class="text-blue-500">&rarr;</span> {{ $flight->arrival_airport }}
                                    </p>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 italic py-4">Data penerbangan belum diinput.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 flex-1">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <x-heroicon-m-building-office-2 class="w-5 h-5 text-purple-500" /> Akomodasi
                            </h3>
                            
                            <button wire:click="exportRoomingPdf" class="flex items-center gap-1 text-[10px] font-bold text-white bg-purple-600 px-3 py-1.5 rounded hover:bg-purple-700 transition shadow">
                                <x-heroicon-m-arrow-down-tray class="w-3 h-3" /> PDF
                            </button>
                        </div>

                        @if(isset($this->batchReportData['hotels']) && $this->batchReportData['hotels']->count() > 0)
                            <div class="space-y-4">
                                @foreach($this->batchReportData['hotels'] as $hotel)
                                <div class="flex gap-4 border-l-2 border-purple-200 dark:border-purple-800 pl-4">
                                    <div class="w-12 text-center shrink-0">
                                        <span class="block text-xl font-bold text-gray-800 dark:text-white">
                                            {{ Carbon::parse($hotel->check_in)->format('d') }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 uppercase">
                                            {{ Carbon::parse($hotel->check_in)->format('M') }}
                                        </span>
                                        <span class="block mt-1 text-[9px] bg-purple-100 text-purple-700 rounded px-1 py-0.5 font-bold">
                                            {{ Carbon::parse($hotel->check_in)->diffInDays(\Carbon\Carbon::parse($hotel->check_out)) }} Malam
                                        </span>
                                    </div>

                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">{{ $hotel->hotel_name }}</h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-gray-500 flex items-center gap-1">
                                                <x-heroicon-m-map-pin class="w-3 h-3 text-purple-400" />
                                                {{ $hotel->city }}
                                            </span>
                                            @if(isset($hotel->star))
                                            <span class="flex text-yellow-400 text-[10px]">
                                                @for($i = 0; $i < $hotel->star; $i++) ★ @endfor
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6 border border-dashed border-gray-200 dark:border-zinc-700 rounded-xl">
                                <x-heroicon-o-building-office class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                <p class="text-xs text-gray-400">Data hotel belum diinput di paket ini.</p>
                            </div>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 h-full">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <x-heroicon-m-document-text class="w-5 h-5 text-orange-500" /> Dokumen & Logistik
                            </h3>
                            <button wire:click="exportPdf" class="flex items-center gap-1 text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded hover:bg-indigo-100 transition">
                                <x-heroicon-m-arrow-down-tray class="w-3 h-3" /> PDF
                            </button>
                        </div>

                        <div class="space-y-6">
                            @php $pax = $this->batchReportData['stats']['pax_count'] > 0 ? $this->batchReportData['stats']['pax_count'] : 1; @endphp
                            
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="text-gray-600 dark:text-zinc-400">Paspor Terkumpul</span>
                                    <span class="text-gray-900 dark:text-white">{{ $this->batchReportData['stats']['passport'] }} / {{ $pax }}</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-blue-500 h-full transition-all duration-1000" style="width: {{ ($this->batchReportData['stats']['passport'] / $pax) * 100 }}%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="text-gray-600 dark:text-zinc-400">Visa Issued</span>
                                    <span class="text-gray-900 dark:text-white">{{ $this->batchReportData['stats']['visa'] }} / {{ $pax }}</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-green-500 h-full transition-all duration-1000" style="width: {{ ($this->batchReportData['stats']['visa'] / $pax) * 100 }}%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="text-gray-600 dark:text-zinc-400">Logistik Terdistribusi</span>
                                    <span class="text-gray-900 dark:text-white">{{ $this->batchReportData['stats']['logistics'] }} / {{ $pax }}</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-orange-500 h-full transition-all duration-1000" style="width: {{ ($this->batchReportData['stats']['logistics'] / $pax) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-white/5 flex flex-col h-full">
                        <div class="p-4 border-b border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-zinc-800/50 rounded-t-2xl">
                            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Persiapan (Pre)
                            </h3>
                        </div>
                        <div class="p-4 space-y-4 flex-1 overflow-y-auto max-h-[400px] custom-scrollbar">
                            @forelse($this->batchReportData['rundown']['pre'] as $rd)
                            <div class="relative pl-4 border-l-2 border-blue-100 dark:border-blue-900">
                                <div class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full bg-blue-400"></div>
                                <p class="text-xs font-bold text-gray-500 mb-0.5">
                                    {{Carbon::parse($rd->date)->format('d M Y') }}
                                </p>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white leading-tight">{{ $rd->activity }}</h4>
                                <p class="text-[10px] text-gray-400 mt-1">{{Carbon::parse($rd->time_start)->format('H:i') }} • {{ $rd->location }}</p>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 italic text-center py-4">Tidak ada kegiatan.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border-2 border-purple-100 dark:border-purple-900/30 shadow-lg flex flex-col h-full relative overflow-hidden">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                        <div class="p-4 border-b border-gray-100 dark:border-white/5 bg-purple-50 dark:bg-purple-900/10">
                            <h3 class="font-bold text-purple-900 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                Saat Umrah (During)
                            </h3>
                        </div>
                        <div class="p-4 space-y-4 flex-1 overflow-y-auto max-h-[400px] custom-scrollbar">
                            @forelse($this->batchReportData['rundown']['during'] as $rd)
                            <div class="relative pl-4 border-l-2 border-purple-200 dark:border-purple-800">
                                <div class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full bg-purple-500"></div>
                                <span class="inline-block px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 text-[10px] font-bold mb-1">
                                    HARI KE-{{ $rd->day_number }}
                                </span>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white leading-tight">{{ $rd->activity }}</h4>
                                <p class="text-[10px] text-gray-400 mt-1">{{Carbon::parse($rd->time_start)->format('H:i') }} • {{ $rd->location }}</p>
                                @if($rd->description)
                                    <p class="text-[10px] text-gray-500 mt-1 italic bg-gray-50 dark:bg-zinc-800 p-1 rounded">"{{ Str::limit($rd->description, 50) }}"</p>
                                @endif
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 italic text-center py-4">Belum ada rundown ibadah.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-white/5 flex flex-col h-full">
                        <div class="p-4 border-b border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-zinc-800/50 rounded-t-2xl">
                            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                Pasca Umrah (Post)
                            </h3>
                        </div>
                        <div class="p-4 space-y-4 flex-1 overflow-y-auto max-h-[400px] custom-scrollbar">
                            @forelse($this->batchReportData['rundown']['post'] as $rd)
                            <div class="relative pl-4 border-l-2 border-orange-100 dark:border-orange-900">
                                <div class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full bg-orange-400"></div>
                                <p class="text-xs font-bold text-gray-500 mb-0.5">
                                    {{ Carbon::parse($rd->date)->format('d M Y') }}
                                </p>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white leading-tight">{{ $rd->activity }}</h4>
                                <p class="text-[10px] text-gray-400 mt-1">{{ Carbon::parse($rd->time_start)->format('H:i') }} • {{ $rd->location }}</p>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 italic text-center py-4">Tidak ada kegiatan.</p>
                            @endforelse
                        </div>
                    </div>

                </div>

            </div>

            @else
            <div class="text-center py-20 bg-white dark:bg-zinc-900 rounded-2xl border border-dashed border-gray-200 dark:border-white/10">
                <x-heroicon-o-cube class="w-16 h-16 text-gray-300 dark:text-zinc-700 mx-auto mb-4" />
                <h3 class="text-lg font-bold text-gray-500 dark:text-zinc-500">Pilih Batch Keberangkatan</h3>
                <p class="text-xs text-gray-400 dark:text-zinc-600">Pilih paket umrah di atas untuk melihat status seat, keuangan, dan operasional.</p>
            </div>
            @endif

        </div>
        @endif

        @if($activeTab === 'finance')
        <div class="animate-fade-in space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-emerald-600 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-emerald-100 text-sm font-medium mb-1 flex items-center gap-2">
                            <x-heroicon-m-arrow-trending-up class="w-4 h-4"/> Pemasukan
                        </p>
                        <h2 class="text-3xl font-black">Rp {{ number_format($this->financeStats['income_month'] / 1000000, 1) }} Jt</h2>
                    </div>
                    <x-heroicon-o-banknotes class="absolute -right-6 -bottom-6 w-32 h-32 text-white/20 group-hover:scale-110 transition duration-500" />
                </div>
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 relative">
                    <p class="text-gray-500 dark:text-zinc-400 text-sm font-medium mb-1 flex items-center gap-2">
                        <x-heroicon-m-arrow-trending-down class="w-4 h-4 text-red-500"/> Pengeluaran
                    </p>
                    <h2 class="text-3xl font-black text-red-600">Rp {{ number_format($this->financeStats['expense_month'] / 1000000, 1) }} Jt</h2>
                </div>
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 relative">
                    <p class="text-gray-500 dark:text-zinc-400 text-sm font-medium mb-1 flex items-center gap-2">
                        <x-heroicon-m-scale class="w-4 h-4 text-gray-400"/> Gross Profit
                    </p>
                    <h2 class="text-3xl font-black text-gray-800 dark:text-white">
                        Rp {{ number_format(($this->financeStats['income_month'] - $this->financeStats['expense_month']) / 1000000, 1) }} Jt
                    </h2>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-gray-200 dark:border-white/5 shadow-sm">
                <h3 class="font-bold text-gray-800 dark:text-white mb-4 text-sm uppercase flex items-center gap-2">
                    <x-heroicon-m-wallet class="w-4 h-4 text-gray-400"/> Posisi Saldo
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($this->financeStats['wallets'] as $wallet)
                    <div class="p-4 rounded-xl border transition hover:shadow-md {{ $wallet->type == 'bank' ? 'bg-blue-50/50 border-blue-200 dark:bg-blue-900/10 dark:border-blue-800' : 'bg-yellow-50/50 border-yellow-200 dark:bg-yellow-900/10 dark:border-yellow-800' }}">
                        <div class="flex justify-between items-start">
                            <p class="text-xs text-gray-500 dark:text-zinc-400 font-bold uppercase">{{ $wallet->name }}</p>
                            @if($wallet->type == 'bank') <x-heroicon-m-building-library class="w-4 h-4 text-blue-400"/> @else <x-heroicon-m-banknotes class="w-4 h-4 text-yellow-500"/> @endif
                        </div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">Rp {{ number_format($wallet->balance, 0, ',', '.') }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="p-5 border-b border-gray-100 dark:border-white/5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-m-calendar-days class="w-5 h-5 text-indigo-500"/>
                        Rekapan Harian
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                        Menampilkan data dari <span class="font-bold text-indigo-600">{{ Carbon::parse($dateStart)->translatedFormat('d M') }}</span> s/d <span class="font-bold text-indigo-600">{{ Carbon::parse($dateEnd)->translatedFormat('d M') }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-2 bg-gray-50 dark:bg-zinc-800 p-1.5 rounded-lg border border-gray-200 dark:border-white/10">
                    <input type="date" wire:model.live="dateStart" 
                        class="bg-transparent border-none text-xs font-bold text-gray-600 dark:text-gray-300 focus:ring-0 p-1 cursor-pointer">
                    <span class="text-gray-400">-</span>
                    <input type="date" wire:model.live="dateEnd" 
                        class="bg-transparent border-none text-xs font-bold text-gray-600 dark:text-gray-300 focus:ring-0 p-1 cursor-pointer">
                </div>

            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-zinc-800 text-xs text-gray-500 uppercase font-bold">
                        <tr>
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4 text-emerald-600">Pemasukan</th>
                            <th class="px-6 py-4 text-red-600">Pengeluaran</th>
                            <th class="px-6 py-4">Saldo Petty Cash</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @forelse($this->dailyFinanceRecap as $day)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-center justify-center bg-gray-100 dark:bg-zinc-700 w-10 h-10 rounded-lg text-xs font-bold text-gray-500 dark:text-zinc-300">
                                        <span>{{ $day['date_obj']->format('d') }}</span>
                                        <span class="text-[9px] uppercase">{{ $day['date_obj']->format('M') }}</span>
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-800 dark:text-white block">
                                            {{ $day['date_obj']->translatedFormat('l') }}
                                        </span>
                                        @if($day['date_obj']->isToday())
                                            <span class="text-[10px] text-green-600 font-bold bg-green-100 dark:bg-green-900/30 px-1.5 py-0.5 rounded">HARI INI</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="font-bold text-emerald-600">
                                    + Rp {{ number_format($day['income'], 0, ',', '.') }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="font-bold text-red-500">
                                    - Rp {{ number_format($day['expense'], 0, ',', '.') }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="font-mono text-gray-600 dark:text-zinc-300 font-bold">
                                    Rp {{ number_format($day['petty_cash_balance'], 0, ',', '.') }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <button wire:click="downloadDailyReport('{{ $day['date_str'] }}')" 
                                    class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition" 
                                    title="Download Laporan PDF">
                                    <x-heroicon-m-document-arrow-down class="w-6 h-6 mx-auto" />
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">
                                Tidak ada data pada rentang tanggal ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($activeTab === 'marketing')
        <div class="animate-fade-in space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <div wire:click="showLeadsDetail('personal')" 
                    class="bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-white/5 cursor-pointer hover:shadow-md hover:border-blue-300 transition group relative">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                            <x-heroicon-o-user class="w-5 h-5" />
                        </div>
                        <span class="text-xs font-bold text-gray-500 uppercase group-hover:text-blue-600">Personal Leads</span>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white">{{ $this->marketingStats['leads_personal'] }}</h2>
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition">
                        <x-heroicon-m-magnifying-glass-plus class="w-5 h-5 text-blue-400" />
                    </div>
                </div>

                <div wire:click="showLeadsDetail('corporate')" 
                    class="bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-white/5 cursor-pointer hover:shadow-md hover:border-purple-300 transition group relative">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition">
                            <x-heroicon-o-building-office class="w-5 h-5" />
                        </div>
                        <span class="text-xs font-bold text-gray-500 uppercase group-hover:text-purple-600">Corporate Leads</span>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white">{{ $this->marketingStats['leads_corporate'] }}</h2>
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition">
                        <x-heroicon-m-magnifying-glass-plus class="w-5 h-5 text-purple-400" />
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-white/5">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-yellow-600">
                            <x-heroicon-o-funnel class="w-5 h-5" />
                        </div>
                        <span class="text-xs font-bold text-gray-500 uppercase">Conversion Rate</span>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white">
                        {{ number_format($this->marketingStats['conversion_rate'], 1) }}%
                    </h2>
                    <p class="text-[10px] text-gray-400">Leads to Closing</p>
                </div>

                <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 text-white p-4 rounded-xl shadow-lg relative overflow-hidden">
                    <div class="relative z-10">
                        <span class="text-xs font-bold text-indigo-200 uppercase">Total Closing vs Target</span>
                        <div class="flex items-end gap-2 mt-1">
                            <h2 class="text-3xl font-black">{{ $this->marketingStats['total_closing'] }}</h2>
                            <span class="text-sm font-medium mb-1 opacity-80">/ {{ $this->marketingStats['global_target'] }}</span>
                        </div>
                        @php
    $globalPercent = $this->marketingStats['global_target'] > 0
        ? ($this->marketingStats['total_closing'] / $this->marketingStats['global_target']) * 100
        : 0;
                        @endphp
                        <div class="w-full bg-black/20 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-white h-full rounded-full" style="width: {{ min($globalPercent, 100) }}%"></div>
                        </div>
                        <p class="text-[10px] mt-1 text-indigo-200 text-right">{{ number_format($globalPercent, 0) }}% Achieved</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-white/5 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-m-trophy class="w-5 h-5 text-yellow-500" />
                        Sales Team Performance
                    </h3>
                    <span class="text-xs bg-gray-100 dark:bg-zinc-800 px-2 py-1 rounded text-gray-500">Bulan Ini</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-zinc-800 text-xs text-gray-500 uppercase font-bold">
                            <tr>
                                <th class="px-6 py-3">Nama Sales</th>
                                <th class="px-6 py-3 text-center">Leads</th>
                                <th class="px-6 py-3 text-center">Closing</th>
                                <th class="px-6 py-3 text-center">Target</th>
                                <th class="px-6 py-3 text-center">Achievement</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach($this->marketingStats['sales_team'] as $sales)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-3 font-bold text-gray-800 dark:text-white">
                                    {{ $sales->full_name }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span class="bg-blue-50 dark:bg-blue-900/20 text-blue-600 px-2 py-1 rounded text-xs font-bold">
                                        {{ $sales->total_leads_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center font-bold text-gray-800 dark:text-white">
                                    {{ $sales->closing_count }}
                                </td>
                                <td class="px-6 py-3 text-center text-gray-500">
                                    {{ $sales->current_target }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-24 bg-gray-200 dark:bg-zinc-700 h-2 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $sales->is_achieved ? 'bg-emerald-500' : 'bg-red-500' }}" 
                                                style="width: {{ min($sales->achievement_percent, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold {{ $sales->is_achieved ? 'text-emerald-600' : 'text-red-500' }}">
                                            {{ number_format($sales->achievement_percent, 0) }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    @if($sales->is_achieved)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                            <x-heroicon-m-check-badge class="w-3 h-3" /> Achieve
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                            <x-heroicon-m-exclamation-circle class="w-3 h-3" /> Under
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
                
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 p-6">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                        <x-heroicon-m-star class="w-5 h-5 text-yellow-500" /> Top 5 Agen Bulan Ini
                    </h3>
                    <div class="space-y-4">
                        @forelse($this->marketingStats['top_agents'] as $idx => $agent)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-800 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-yellow-100 text-yellow-700 rounded-full flex items-center justify-center font-black text-xs">
                                    #{{ $idx + 1 }}
                                </div>
                                <div>
                                    <p class="font-bold text-sm text-gray-800 dark:text-white">{{ $agent->name }}</p>
                                    <p class="text-[10px] text-gray-500">{{ $agent->city ?? 'Kota -' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="block font-black text-lg text-gray-800 dark:text-white">{{ $agent->bookings_count }}</span>
                                <span class="text-[10px] text-gray-400 uppercase">Jamaah</span>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 italic text-center py-4">Belum ada performa agen bulan ini.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <x-heroicon-o-bell-alert class="w-32 h-32 text-red-600" />
                    </div>
                    <h3 class="font-bold text-red-600 mb-4 flex items-center gap-2 relative z-10">
                        <x-heroicon-m-exclamation-triangle class="w-5 h-5" /> Perlu Follow Up!
                    </h3>
                    <p class="text-xs text-gray-500 mb-4 relative z-10">Daftar agen tanpa jamaah dalam 3 bulan terakhir.</p>
                    
                    <div class="space-y-2 max-h-[300px] overflow-y-auto custom-scrollbar relative z-10">
                        @forelse($this->marketingStats['dormant_agents'] as $agent)
                        <div class="flex items-center justify-between p-2 border-b border-gray-100 dark:border-zinc-800 last:border-0 hover:bg-red-50 dark:hover:bg-red-900/10 transition rounded">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-sm font-medium text-gray-700 dark:text-zinc-300">{{ $agent->name }}</span>
                            </div>
                            <a href="#" class="text-[10px] font-bold text-indigo-600 hover:underline">Hubungi</a>
                        </div>
                        @empty
                        <div class="text-center py-8 text-green-600 font-bold">
                            <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2" />
                            Semua Agen Aktif! 🎉
                        </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
        @endif

        @if($activeTab === 'media')
        <div class="animate-fade-in space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border-l-4 border-yellow-400 shadow-sm relative overflow-hidden">
                    <div class="flex justify-between items-start mb-2">
                        <p class="text-yellow-600 dark:text-yellow-500 font-bold text-xs uppercase tracking-wider">Antrian Desain</p>
                        <x-heroicon-o-paint-brush class="w-6 h-6 text-yellow-400 opacity-50"/>
                    </div>
                    <h2 class="text-4xl font-black text-gray-900 dark:text-white">{{ $this->mediaStats['requests_pending'] }}</h2>
                    <p class="text-xs text-gray-400 mt-2">Request belum dikerjakan</p>
                </div>

                <div wire:click="showMediaDetail('published')" 
                    class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border-l-4 border-green-500 shadow-sm relative overflow-hidden cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800 transition group">
                    <div class="flex justify-between items-start mb-2">
                        <p class="text-green-600 dark:text-green-500 font-bold text-xs uppercase tracking-wider">Content Published</p>
                        <x-heroicon-o-check-badge class="w-6 h-6 text-green-500 opacity-50"/>
                    </div>
                    <h2 class="text-4xl font-black text-gray-900 dark:text-white">{{ $this->mediaStats['published_month'] }}</h2>
                    <p class="text-xs text-gray-400 mt-2">Tayang bulan ini</p>
                    
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition transform group-hover:scale-110">
                        <x-heroicon-m-eye class="w-6 h-6 text-green-600" />
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 p-6 rounded-2xl shadow-lg text-white flex flex-col justify-center items-center text-center relative overflow-hidden">
                    <x-heroicon-o-photo class="w-24 h-24 absolute -right-6 -bottom-6 text-white/10" />
                    
                    <h3 class="font-bold text-lg mb-1 relative z-10">Creative Assets</h3>
                    <p class="text-xs text-indigo-200 mb-4 relative z-10">Bank Foto, Video & Dokumen</p>
                    
                    <button wire:click="showMediaDetail('assets')" 
                        class="relative z-10 bg-white text-indigo-700 px-6 py-2 rounded-lg font-bold text-sm hover:bg-indigo-50 transition shadow-sm flex items-center gap-2">
                        <x-heroicon-m-folder-open class="w-4 h-4" />
                        Buka Penyimpanan
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-white/5 shadow-sm overflow-hidden">
            
                <div class="p-5 border-b border-gray-100 dark:border-white/5 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-m-calendar class="w-5 h-5 text-pink-500" />
                        Rundown Konten Bulan Ini
                    </h3>
                </div>

                <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto custom-scrollbar">
                    
                    @forelse($this->mediaStats['content_schedule'] as $schedule)
                    <div class="flex gap-4 group">
                        
                        <div class="w-16 text-center pt-2 shrink-0">
                            <span class="block text-2xl font-black text-gray-800 dark:text-white">
                                {{ $schedule->scheduled_date->format('d') }}
                            </span>
                            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">
                                {{ $schedule->scheduled_date->format('M') }}
                            </span>
                            <span class="block text-[10px] text-gray-300 mt-1">
                                {{ $schedule->scheduled_date->format('D') }}
                            </span>
                        </div>

                        <div class="flex-1 bg-white dark:bg-zinc-950 p-4 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-md hover:border-pink-200 dark:hover:border-pink-900/30 transition relative overflow-hidden">
                            
                            <div class="absolute top-4 right-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-black tracking-wide
                                    {{ match ($schedule->status) {
            'published' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'ready' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'draft' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            default => 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-zinc-400'
        } }}">
                                    {{ $schedule->status }}
                                </span>
                            </div>

                            <div class="pr-20"> <h4 class="font-bold text-gray-900 dark:text-white text-lg leading-tight">
                                    {{ $schedule->title }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-2 line-clamp-2">
                                    {{ $schedule->caption_draft ?? 'Belum ada caption...' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-50 dark:border-white/5">
                                
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
                'instagram' => 'hover:bg-pink-50 hover:text-pink-600 hover:border-pink-200 dark:hover:bg-pink-900/20',
                'tiktok' => 'hover:bg-gray-100 hover:text-black hover:border-gray-300 dark:hover:bg-white/10 dark:hover:text-white',
                'facebook' => 'hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 dark:hover:bg-blue-900/20',
                'youtube' => 'hover:bg-red-50 hover:text-red-600 hover:border-red-200 dark:hover:bg-red-900/20',
                default => 'hover:bg-gray-50 hover:text-gray-600'
            };
                                    @endphp

                                    @if($isClickable)
                                        <a href="{{ $url }}" target="_blank" 
                                        class="flex items-center gap-1.5 text-xs font-bold text-gray-600 dark:text-zinc-400 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900 transition {{ $style }}">
                                            
                                            @if($plat == 'instagram') <span class="text-pink-500">📸</span>
                                            @elseif($plat == 'tiktok') <span class="text-black dark:text-white">🎵</span>
                                            @elseif($plat == 'facebook') <span class="text-blue-600">f</span>
                                            @elseif($plat == 'youtube') <span class="text-red-600">▶</span>
                                            @endif

                                            {{ ucfirst($plat) }}
                                            <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3 ml-1 opacity-50" />
                                        </a>
                                    @else
                                        <span class="flex items-center gap-1.5 text-xs font-medium text-gray-400 px-3 py-1.5 rounded-lg border border-dashed border-gray-200 dark:border-zinc-800 cursor-not-allowed opacity-60">
                                            {{ ucfirst($plat) }}
                                        </span>
                                    @endif
                                @endforeach
                                
                                @if(empty($platforms))
                                    <span class="text-[10px] text-gray-400 italic mt-1">Belum ada platform dipilih.</span>
                                @endif
                            </div>

                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12">
                        <x-heroicon-o-calendar class="w-12 h-12 text-gray-300 mx-auto mb-2" />
                        <p class="text-gray-500 font-medium">Belum ada jadwal konten bulan ini.</p>
                    </div>
                    @endforelse

                </div>
            </div>

        </div>

        <div x-show="$wire.showMediaListModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
            <div wire:click="$set('showMediaListModal', false)" class="fixed inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
            
            <div class="relative bg-white dark:bg-zinc-900 w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh]" x-transition.move.up>
                
                <div class="p-5 border-b border-gray-100 dark:border-white/10 flex justify-between items-center shrink-0">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        @if($mediaListType === 'published')
                            <x-heroicon-m-check-badge class="w-6 h-6 text-green-500" /> History Publish (Bulan Ini)
                        @else
                            <x-heroicon-m-photo class="w-6 h-6 text-indigo-500" /> Aset Terbaru (Preview)
                        @endif
                    </h3>
                    <button wire:click="$set('showMediaListModal', false)" class="text-gray-400 hover:text-red-500"><x-heroicon-m-x-mark class="w-6 h-6" /></button>
                </div>

                <div class="overflow-y-auto custom-scrollbar p-5 flex-1">
                    
                    @if($mediaListType === 'published')
                        <div class="grid grid-cols-1 gap-4">
                            @forelse($mediaListData as $item)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-zinc-800 rounded-xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center font-bold text-xs">
                                        {{ $item->updated_at->format('d M') }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 dark:text-white">{{ $item->title }}</p>
                                        <p class="text-xs text-gray-500">{{ $item->platform }} • PIC: {{ $item->pic_name }}</p>
                                    </div>
                                </div>
                                @if($item->link)
                                <a href="{{ $item->link }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1">
                                    Lihat <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3"/>
                                </a>
                                @endif
                            </div>
                            @empty
                            <p class="text-center text-gray-400 italic">Belum ada konten publish bulan ini.</p>
                            @endforelse
                        </div>

                    @else
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @forelse($mediaListData as $asset)
                            <div class="group relative aspect-square bg-gray-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
                                <div class="absolute inset-0 flex items-center justify-center text-gray-400 bg-gray-200 dark:bg-zinc-800">
                                    <x-heroicon-o-document class="w-8 h-8" />
                                </div>
                                
                                <div class="absolute inset-0 bg-black/60 flex flex-col justify-end p-3 opacity-0 group-hover:opacity-100 transition">
                                    <p class="text-white text-xs font-bold truncate">{{ $asset->file_name ?? 'File Asset' }}</p>
                                    <a href="#" class="mt-2 text-[10px] bg-white text-black px-2 py-1 rounded text-center font-bold">Download</a>
                                </div>
                            </div>
                            @empty
                            <p class="col-span-4 text-center text-gray-400 italic py-10">Belum ada aset tersimpan.</p>
                            @endforelse
                        </div>
                        <div class="mt-6 text-center">
                            <a href="{{ route('creative') }}" class="text-sm font-bold text-indigo-600 hover:underline">Ke Halaman Creative Studio Full &rarr;</a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
        @endif

        @if($activeTab === 'hr')
        <div class="animate-fade-in space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-gray-200 dark:border-white/5 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
                        <x-heroicon-o-users class="w-8 h-8" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase">Total Karyawan</p>
                        <h2 class="text-3xl font-black text-gray-900 dark:text-white">{{ $this->hrStats['total_employees'] }}</h2>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-gray-200 dark:border-white/5 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                        <x-heroicon-o-clipboard-document-list class="w-8 h-8" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase">Tugas Hari Ini</p>
                        <h2 class="text-3xl font-black text-gray-900 dark:text-white">{{ $this->hrStats['active_tasks'] }}</h2>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-gray-200 dark:border-white/5 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                        <x-heroicon-o-chart-bar class="w-8 h-8" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase">Rata-rata Performa</p>
                        <h2 class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($this->hrStats['avg_performance'], 0) }}%</h2>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-white/5 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-white/5">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-m-user-group class="w-5 h-5 text-orange-500" />
                        Monitoring Produktivitas
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-zinc-800 text-xs text-gray-500 uppercase font-bold">
                            <tr>
                                <th class="px-6 py-3">Nama Karyawan</th>
                                <th class="px-6 py-3">Departemen</th>
                                <th class="px-6 py-3 text-center">Progres Hari Ini</th>
                                <th class="px-6 py-3 text-center">Performa Bulan Ini</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach($this->hrStats['employees'] as $emp)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                                
                                <td class="px-6 py-3">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $emp->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $emp->position }}</p>
                                </td>

                                <td class="px-6 py-3">
                                    <span class="px-2 py-1 rounded text-xs font-bold bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ $emp->departmentRel->name ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-6 py-3 text-center">
                                    @php
                                        $dailyText = $emp->daily_total === 0 ? 'Tidak Ada Tugas' : "{$emp->daily_done} / {$emp->daily_total} Selesai";

                                        $badgeColor = 'bg-gray-100 text-gray-500';
                                        if ($emp->daily_total > 0) {
                                            if ($emp->daily_done == $emp->daily_total) {
                                                $badgeColor = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; // Success
                                            } elseif ($emp->daily_done == 0) {
                                                $badgeColor = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; // Danger
                                            } else {
                                                $badgeColor = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'; // Warning
                                            }
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $badgeColor }}">
                                        {{ $dailyText }}
                                    </span>
                                </td>

                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if($emp->monthly_percent == 100)
                                            <x-heroicon-m-trophy class="w-4 h-4 text-yellow-500" />
                                        @endif
                                        
                                        <span class="font-bold {{ $emp->monthly_percent >= 80 ? 'text-emerald-600' : ($emp->monthly_percent >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $emp->monthly_percent }}%
                                        </span>
                                    </div>
                                    <div class="w-20 mx-auto bg-gray-200 dark:bg-zinc-700 h-1.5 rounded-full mt-1 overflow-hidden">
                                        <div class="h-full rounded-full {{ $emp->monthly_percent >= 80 ? 'bg-emerald-500' : ($emp->monthly_percent >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                            style="width: {{ $emp->monthly_percent }}%"></div>
                                    </div>
                                </td>

                                <td class="px-6 py-3 text-center">
                                    <button wire:click="viewEmployeeTasks({{ $emp->id }})" class="text-gray-400 hover:text-indigo-600 transition">
                                        <x-heroicon-m-eye class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="$wire.showHrModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
            
            <div wire:click="$set('showHrModal', false)" class="fixed inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>

            <div class="relative bg-white dark:bg-zinc-900 w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh]" x-transition.move.up>
                
                <div class="p-5 border-b border-gray-100 dark:border-white/10 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tugas Hari Ini</h3>
                        <p class="text-xs text-gray-500">{{ $selectedEmployeeName }}</p>
                    </div>
                    <button wire:click="$set('showHrModal', false)" class="text-gray-400 hover:text-red-500"><x-heroicon-m-x-mark class="w-6 h-6" /></button>
                </div>

                <div class="overflow-y-auto custom-scrollbar p-6 space-y-4">
                    @forelse($employeeTasks as $task)
                    <div class="bg-gray-50 dark:bg-zinc-800 p-4 rounded-xl border border-gray-100 dark:border-zinc-700">
                        
                        <h4 class="font-bold text-gray-800 dark:text-white mb-3 text-base">{{ $task->title }}</h4>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm mb-3">
                            
                            <div>
                                <span class="text-xs text-gray-400 block mb-1">Tipe</span>
                                @php
                                    $freq = $task->template->frequency ?? 'daily';
                                    $freqColor = match ($freq) {
                                        'daily' => 'bg-green-100 text-green-700',
                                        'weekly' => 'bg-yellow-100 text-yellow-700',
                                        'monthly' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-gray-100 text-gray-600'
                                    };                                                                                          
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase {{ $freqColor }}">
                                    {{ $freq }}
                                </span>
                            </div>

                            <div>
                                <span class="text-xs text-gray-400 block mb-1">Status</span>
                                @php
                                    $statusColor = match ($task->status) {
                                        'completed' => 'bg-green-100 text-green-700',
                                        'pending' => 'bg-red-100 text-red-700',
                                        'in_progress' => 'bg-yellow-100 text-yellow-700',
                                        default => 'bg-gray-100 text-gray-600'
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase {{ $statusColor }}">
                                    {{ str_replace('_', ' ', $task->status) }}
                                </span>
                            </div>

                            <div>
                                <span class="text-xs text-gray-400 block mb-1">Deadline</span>
                                <div class="flex items-center gap-1 {{ $task->due_date < now() && $task->status !== 'completed' ? 'text-red-500 font-bold' : 'text-gray-700 dark:text-zinc-300' }}">
                                    <x-heroicon-m-clock class="w-4 h-4" />
                                    {{ Carbon::parse($task->due_date)->format('d M, H:i') }}
                                </div>
                            </div>

                        </div>

                        @if($task->status === 'completed')
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-zinc-700">
                            <span class="text-xs text-gray-400 block mb-1">Catatan Penyelesaian</span>
                            <p class="text-sm text-gray-600 dark:text-zinc-400 italic">
                                "{{ $task->completion_note ?? '-' }}"
                            </p>
                        </div>
                        @endif

                    </div>
                    @empty
                    <div class="text-center py-10">
                        <x-heroicon-o-clipboard class="w-12 h-12 text-gray-300 mx-auto mb-2" />
                        <p class="text-gray-500">Tidak ada tugas hari ini.</p>
                    </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-white/10 flex justify-end">
                    <button wire:click="$set('showHrModal', false)" class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-800 dark:text-white rounded-lg font-bold text-xs hover:bg-gray-300 transition">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
        @endif

    </div>

    <div x-show="$wire.showLeadsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-transition.opacity>
    
        <div wire:click="$set('showLeadsModal', false)" class="fixed inset-0 bg-black/60 backdrop-blur-sm cursor-pointer"></div>

        <div class="relative bg-white dark:bg-zinc-900 w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh]" x-transition.move.up>
            
            <div class="p-5 border-b border-gray-100 dark:border-white/10 flex justify-between items-center shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        @if($leadsDetailType === 'corporate')
                            <x-heroicon-m-building-office class="w-6 h-6 text-purple-600" />
                            Detail Corporate Leads
                        @else
                            <x-heroicon-m-user class="w-6 h-6 text-blue-600" />
                            Detail Personal Leads
                        @endif
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">Data masuk bulan ini.</p>
                </div>
                <button wire:click="$set('showLeadsModal', false)" class="text-gray-400 hover:text-red-500 transition">
                    <x-heroicon-m-x-mark class="w-6 h-6" />
                </button>
            </div>

            <div class="overflow-y-auto custom-scrollbar p-0 flex-1">
                <table class="w-full text-sm text-left whitespace-nowrap">
                    <thead class="bg-gray-50 dark:bg-zinc-800 text-xs text-gray-500 uppercase font-bold sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3">Tanggal</th>
                            @if($leadsDetailType === 'corporate')
                                <th class="px-6 py-3">Perusahaan & PIC</th>
                                <th class="px-6 py-3">Est. Budget/Pax</th>
                            @else
                                <th class="px-6 py-3">Nama Jamaah</th>
                                <th class="px-6 py-3">Kota</th>
                            @endif
                            <th class="px-6 py-3">Sales PIC</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @forelse($leadsData as $lead)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                            
                            <td class="px-6 py-3 text-gray-500 text-xs">
                                {{ $lead->created_at->format('d M Y') }}
                            </td>

                            @if($leadsDetailType === 'corporate')
                                <td class="px-6 py-3">
                                    <p class="font-bold text-gray-800 dark:text-white">{{ $lead->company_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $lead->pic_name }} - {{ $lead->pic_phone }}</p>
                                </td>
                                <td class="px-6 py-3">
                                    <p class="font-mono text-xs text-gray-600 dark:text-zinc-300">
                                        Rp {{ number_format($lead->budget_estimation, 0, ',', '.') }}
                                    </p>
                                    <p class="text-[10px] text-gray-400">{{ $lead->potential_pax }} Pax</p>
                                </td>
                            @else
                                <td class="px-6 py-3">
                                    <p class="font-bold text-gray-800 dark:text-white">{{ $lead->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $lead->phone }}</p>
                                </td>
                                <td class="px-6 py-3 text-gray-500">
                                    {{ $lead->city }}
                                </td>
                            @endif

                            <td class="px-6 py-3">
                                <span class="bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-zinc-300 px-2 py-1 rounded text-xs font-bold">
                                    {{ $lead->sales->full_name ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-3 text-center">
                                @php
                                    $status = strtolower($lead->status);
                                    $color = match ($status) {
                                        'hot', 'deal', 'closing' => 'bg-emerald-100 text-emerald-700',
                                        'warm', 'negotiation' => 'bg-yellow-100 text-yellow-700',
                                        'lost' => 'bg-red-100 text-red-700',
                                        default => 'bg-blue-100 text-blue-700'
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $color }}">
                                    {{ $lead->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">
                                Belum ada leads tipe ini bulan ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-100 dark:border-white/10 bg-gray-50 dark:bg-zinc-900 rounded-b-2xl flex justify-end">
                <button wire:click="$set('showLeadsModal', false)" class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-800 dark:text-white rounded-lg font-bold hover:bg-gray-300 transition text-xs">
                    Tutup
                </button>
            </div>

        </div>
    </div>

</div>
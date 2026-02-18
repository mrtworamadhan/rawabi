@if (session()->has('success') || session()->has('error'))
<div class="fixed top-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none">
    
    @if (session()->has('success'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 4000)"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 translate-x-20"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-500"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-20"
         class="pointer-events-auto bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border-l-4 border-emerald-500 shadow-2xl shadow-emerald-500/10 p-4 rounded-2xl flex items-center gap-4 min-w-[280px] md:min-w-[320px]">
        
        <div class="w-10 h-10 bg-emerald-500/10 text-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <x-heroicon-s-check-circle class="w-6 h-6" />
        </div>
        
        <div class="flex-1">
            <h4 class="text-xs font-black text-emerald-600 uppercase tracking-widest">Berhasil</h4>
            <p class="text-sm font-bold text-slate-700 dark:text-zinc-200">{{ session('success') }}</p>
        </div>

        <button @click="show = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
            <x-heroicon-s-x-mark class="w-4 h-4" />
        </button>
    </div>
    @endif

    @if (session()->has('error'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 5000)"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 translate-x-20"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-500"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-20"
         class="pointer-events-auto bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border-l-4 border-red-500 shadow-2xl shadow-red-500/10 p-4 rounded-2xl flex items-center gap-4 min-w-[280px] md:min-w-[320px]">
        
        <div class="w-10 h-10 bg-red-500/10 text-red-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <x-heroicon-s-x-circle class="w-6 h-6" />
        </div>
        
        <div class="flex-1">
            <h4 class="text-xs font-black text-red-600 uppercase tracking-widest">Kesalahan</h4>
            <p class="text-sm font-bold text-slate-700 dark:text-zinc-200">{{ session('error') }}</p>
        </div>

        <button @click="show = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
            <x-heroicon-s-x-mark class="w-4 h-4" />
        </button>
    </div>
    @endif

</div>
@endif
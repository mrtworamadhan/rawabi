<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'POS System' }} - Rawabi Finance</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .text-gradient {
            background: linear-gradient(to right, #FF7A00, #FFB800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #FF7A00; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#09090b] font-sans antialiased h-screen flex flex-col overflow-hidden text-slate-900 dark:text-zinc-100" 
      x-data="{ 
        mobileMenuOpen: false, 
        darkMode: localStorage.getItem('theme') === 'dark',
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) { document.documentElement.classList.add('dark'); }
            else { document.documentElement.classList.remove('dark'); }
        }
      }">

    <nav class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md px-4 py-2.5 flex justify-between items-center border-b border-slate-200 dark:border-white/5 shrink-0 z-50 relative">
        <div class="flex items-center gap-4">
            @hasSection('header')
                @yield('header')
            @else
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-orange-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
                        <x-heroicon-s-currency-dollar class="w-6 h-6" />
                    </div>
                    <div class="flex flex-col">
                        <span class="font-black text-sm md:text-base tracking-tight leading-none uppercase">Finance <span class="text-primary">Center</span></span>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-[9px] font-bold text-slate-400 dark:text-zinc-500 tracking-widest uppercase">Command System v1.0</span>
                        </div>
                    </div>
                </div>
            @endif
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
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-invoice-url', (event) => {
                window.open(event.url, '_blank');
            });
        });
    </script>
</body>
</html>
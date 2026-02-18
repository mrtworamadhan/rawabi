<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"> 
    <title>{{ $title ?? 'Rawabi System' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #3f3f46; }
        .glass-backdrop { backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="bg-gray-50 dark:bg-zinc-950 font-sans antialiased h-screen flex flex-col overflow-hidden text-gray-900 dark:text-zinc-100" 
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
    
    <nav class="bg-white dark:bg-zinc-900 shadow-sm px-4 py-3 flex justify-between items-center border-b border-gray-200 dark:border-white/10 shrink-0 z-40 relative">
        <div class="flex items-center gap-3">
            
            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="md:hidden p-2 -ml-2 text-gray-500 hover:bg-gray-100 dark:text-zinc-400 dark:hover:bg-zinc-800 rounded-lg transition">
                <x-heroicon-o-bars-3 class="w-6 h-6" />
            </button>

            <div class="w-8 h-8 bg-black dark:bg-white rounded-lg flex items-center justify-center text-white dark:text-black font-black text-sm">
                R
            </div>
            <div class="flex flex-col">
                <span class="font-bold text-lg leading-none">REPORT CENTER</span>
                <span class="text-[10px] text-gray-500 dark:text-zinc-500 tracking-wider">EXECUTIVE DASHBOARD</span>
            </div>
        </div>

        <div class="relative" x-data="{ open: false }">
        
            <button @click="open = !open"
                class="h-8 w-8 rounded-full bg-gray-200 dark:bg-zinc-800 flex items-center justify-center font-bold text-xs hover:ring-2 hover:ring-indigo-500 transition cursor-pointer">
                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
            </button>
        
            <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-900 rounded-xl shadow-lg border border-gray-100 dark:border-white/10 py-1 z-50 origin-top-right"
                style="display: none;">
        
                <div class="px-4 py-2 border-b border-gray-100 dark:border-white/5 mb-1">
                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-[10px] text-gray-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                </div>
        
                <a href="/admin"
                    class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-gray-50 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-white/5 transition">
                    <x-heroicon-m-squares-2x2 class="w-4 h-4" />
                    Admin Panel
                </a>
        
                <div class="border-t border-gray-100 dark:border-white/5 my-1"></div>
        
                <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition">
                        <x-heroicon-m-arrow-right-on-rectangle class="w-4 h-4" />
                        Sign Out
                    </button>
                </form>
            </div>
        
        </div>
    </nav>

    <main class="flex-1 flex w-full relative overflow-hidden">
        {{ $slot }}
    </main>

    @livewireScripts
    
</body>
</html>
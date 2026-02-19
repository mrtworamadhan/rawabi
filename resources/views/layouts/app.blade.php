<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Rawabi System App' }}</title>
    
    <meta name="description" content="{{ $description ?? 'Sistem Manajemen Travel Umrah & Haji Terintegrasi. Monitoring Leads, Manifest, Inventory, dan Keuangan Rawabi Travel secara Real-time.' }}">
    <meta name="keywords" content="Rawabi Travel, Sistem Umrah, Manajemen Haji, CRM Travel, ERP Travel, Manifest System">
    <meta name="author" content="Rawabi Tech Team">
    <meta name="robots" content="noindex, nofollow"> <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/brand/logo.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#f59e0b"> 
    <meta name="mobile-web-app-capable" content="yes">
    
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Rawabi System">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/logo.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'Rawabi System - Executive Dashboard' }}">
    <meta property="og:description" content="{{ $description ?? 'Pantau performa bisnis, manifest jamaah, dan keuangan travel dalam satu genggaman.' }}">
    <meta property="og:image" content="{{ asset('images/brand/logo.png') }}">
    <meta property="og:site_name" content="Rawabi System">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Rawabi System - Executive Dashboard' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Pantau performa bisnis, manifest jamaah, dan keuangan travel dalam satu genggaman.' }}">
    <meta name="twitter:image" content="{{ asset('images/brand/logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #3f3f46; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function (reg) {
                    console.log('SW REGISTERED with scope:', reg.scope);
                })
                .catch(function (err) {
                    console.error('SW FAILED:', err);
                });
        });
    }
    </script>
</head>
<body class="bg-slate-50 dark:bg-[#09090b] font-sans antialiased h-screen flex flex-col overflow-hidden text-slate-900 dark:text-zinc-100 selection:bg-primary selection:text-white"
      x-data="{ 
        darkMode: localStorage.getItem('theme') === 'dark',
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) { document.documentElement.classList.add('dark'); }
            else { document.documentElement.classList.remove('dark'); }
        }
      }">

    {{ $slot }}

    <x-toast-notification />

    @livewireScripts

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-invoice-url', (event) => {
                window.open(event.url, '_blank');
            });
        });
    </script>

    <div x-data="{
            showBanner: false,
            deferredPrompt: null,
            init() {
                setTimeout(() => { 
                    if(!localStorage.getItem('hideInstall')) this.showBanner = true; 
                }, 2000);

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    if(!localStorage.getItem('hideInstall')) this.showBanner = true;
                });
            },
            installApp() {
                if (this.deferredPrompt) {
                    this.deferredPrompt.prompt();
                    this.deferredPrompt.userChoice.then((choice) => {
                        if (choice.outcome === 'accepted') {
                            this.closeBanner();
                        }
                        this.deferredPrompt = null;
                    });
                } else {
                    alert('Untuk install manual: Ketuk menu browser (titik tiga atau tombol Share) lalu pilih Add to Home Screen / Tambahkan ke Layar Utama.');
                }
            },
            closeBanner() {
                this.showBanner = false;
                localStorage.setItem('hideInstall', 'true');
            }
        }"
        x-show="showBanner"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="-translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="-translate-y-full opacity-0"
        class="fixed top-0 left-0 w-full z-[100] bg-green-600 text-white shadow-xl border-b border-green-500/50"
        style="display: none;"
        x-cloak
    >
        <div class="px-4 py-3 flex items-center justify-between max-w-md mx-auto pt-safe">
            <div class="flex items-center gap-3">
                <div class="bg-white p-1 rounded-xl shadow-inner">
                    <img src="{{ asset('images/brand/logo.png') }}" alt="Logo" class="w-8 h-8 rounded-lg">
                </div>
                <div>
                    <p class="text-sm font-extrabold leading-tight tracking-wide">Install RawabiSystem</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button @click="installApp()" class="px-5 py-2 bg-white text-green-700 hover:bg-green-50 text-[11px] sm:text-xs font-extrabold rounded-full shadow-md transition transform hover:scale-105 whitespace-nowrap min-w-[80px] text-center">
                    Install
                </button>
                <button @click="closeBanner()" class="text-green-200 hover:text-white transition p-1.5 rounded-full hover:bg-green-700 shrink-0">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</body>
</html>
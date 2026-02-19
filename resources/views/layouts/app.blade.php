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

    
</body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-XXXXXXXXXX');
    </script>

    <title>{{ $title ?? 'Dashboard' }} - Rawabi System</title>

    <meta name="description" content="{{ $description ?? 'Sistem Manajemen Travel Umrah & Haji Terintegrasi. Monitoring Leads, Manifest, Inventory, dan Keuangan Rawabi Travel secara Real-time.' }}">
    <meta name="keywords" content="Rawabi Travel, Sistem Umrah, Manajemen Haji, CRM Travel, ERP Travel, Manifest System">
    <meta name="author" content="Rawabi Tech Team">
    <meta name="robots" content="noindex, nofollow"> <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/brand/logo.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}"> <meta name="theme-color" content="#000000"> <meta name="mobile-web-app-capable" content="yes">
    
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
    
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>

<body x-data="{ scrolled: false, mobileMenuOpen: false }" 
      @scroll.window="scrolled = (window.pageYOffset > 20)"
      class="antialiased public-page font-sans text-gray-800 bg-slate-50">

    <nav :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-lg py-3' : 'bg-transparent py-6'"
         class="fixed top-0 left-0 w-full z-50 transition-all duration-300">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                
                <a href="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-md ring-2 ring-primary/20 group-hover:ring-primary transition-all overflow-hidden">
                        <img src="https://abuuurizal.github.io/rawabizamzam/assets/konfigurasi/logo.jpg" 
                             alt="Logo" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h1 class="font-bold text-xl tracking-wide leading-none" 
                            :class="scrolled ? 'text-gray-900' : 'text-white'">
                            RAWABI <span class="text-primary">ZAMZAM</span>
                        </h1>
                        <p class="text-[10px] tracking-wider uppercase opacity-80"
                           :class="scrolled ? 'text-gray-500' : 'text-gray-200'">
                            Umroh & Haji Khusus
                        </p>
                    </div>
                </a>

                <div class="hidden md:flex items-center gap-8">
                    <a href="/" class="text-sm font-semibold transition hover:text-primary" :class="scrolled ? 'text-gray-700' : 'text-white'">Beranda</a>
                    <a href="/packages" class="text-sm font-semibold transition hover:text-primary" :class="scrolled ? 'text-gray-700' : 'text-white'">Paket Umroh</a>
                    <a href="/tentang-kami" class="text-sm font-semibold transition hover:text-primary" :class="scrolled ? 'text-gray-700' : 'text-white'">Tentang Kami</a>
                    <a href="/artikel" class="text-sm font-semibold transition hover:text-primary" :class="scrolled ? 'text-gray-700' : 'text-white'">Artikel</a>
                </div>

                <div class="hidden md:block">
                    <a href="#" class="btn-gradient px-6 py-2.5 rounded-full text-sm inline-flex items-center gap-2">
                        <i class="bi bi-whatsapp"></i> Hubungi Kami
                    </a>
                </div>

                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="md:hidden text-2xl transition-colors"
                        :class="scrolled ? 'text-gray-800' : 'text-white'">
                    <i class="bi" :class="mobileMenuOpen ? 'bi-x-lg' : 'bi-list'"></i>
                </button>
            </div>
        </div>

        <div x-show="mobileMenuOpen" x-collapse x-cloak
             class="absolute top-full left-0 w-full bg-white shadow-xl border-t border-gray-100 md:hidden">
            <div class="flex flex-col p-4 gap-2">
                <a href="/" class="p-3 rounded-lg hover:bg-slate-50 text-gray-700 font-medium">Beranda</a>
                <a href="#paket" class="p-3 rounded-lg hover:bg-slate-50 text-gray-700 font-medium">Paket Umroh</a>
                <a href="#tentang" class="p-3 rounded-lg hover:bg-slate-50 text-gray-700 font-medium">Tentang Kami</a>
                <a href="#" class="btn-gradient text-center py-3 rounded-lg mt-2">Hubungi Kami</a>
            </div>
        </div>
    </nav>
    <main class="min-h-screen">
        {{ $slot }}
    </main>
    <footer class="bg-dark text-white pt-16 pb-8 border-t-4 border-primary relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="absolute inset-0 opacity-80 pointer-events-none"
                style="
                    background-image: url('/images/ornaments/arabesque.png');
                    background-repeat: repeat;
                    background-size: 200px 200px;
                    filter: brightness(0.5) sepia(1) hue-rotate(10deg) saturate(5);
                ">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                
                <div>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center overflow-hidden">
                             <img src="https://abuuurizal.github.io/rawabizamzam/assets/konfigurasi/logo.jpg" alt="Logo" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold">RAWABI ZAMZAM</h3>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed mb-6">
                        Melayani perjalanan ibadah Umroh dan Haji Khusus dengan amanah, nyaman, dan sesuai sunnah.
                    </p>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-primary transition-colors"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-primary transition-colors"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-primary transition-colors"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6 text-primary">Menu Utama</h4>
                    <ul class="space-y-3 text-sm text-gray-300">
                        <li><a href="/" class="hover:text-primary transition">Beranda</a></li>
                        <li><a href="/paket-umroh" class="hover:text-primary transition">Paket Umroh</a></li>
                        <li><a href="/tentang-kami" class="hover:text-primary transition">Tentang Kami</a></li>
                        <li><a href="/artikel" class="hover:text-primary transition">Artikel Islami</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6 text-primary">Hubungi Kami</h4>
                    <ul class="space-y-4 text-sm text-gray-300">
                        <li class="flex items-start gap-3">
                            <i class="bi bi-geo-alt-fill text-primary mt-1"></i>
                            <span>Bogor, Indonesia</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="bi bi-whatsapp text-primary"></i>
                            <span>+62 812 3456 7890</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="bi bi-envelope-fill text-primary"></i>
                            <span>info@rawabizamzam.com</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6 text-primary">Legalitas Resmi</h4>
                    <div class="bg-white/5 p-4 rounded-xl border border-white/10 backdrop-blur-sm">
                        <p class="text-xs text-gray-400 mb-1">Izin PPIU Kemenag RI</p>
                        <p class="font-bold text-white tracking-wide">No. 05012300125230002</p>
                        <div class="mt-3 flex items-center gap-2 text-green-400 text-xs font-bold">
                            <i class="bi bi-patch-check-fill"></i> Terakreditasi A
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
                <p>&copy; 2026 Rawabi Zamzam. All Rights Reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-white transition">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-white transition">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>
    @livewireScripts
    <script async src="https://www.tiktok.com/embed.js"></script>
    <script async src="//www.instagram.com/embed.js"></script>
</body>
</html>
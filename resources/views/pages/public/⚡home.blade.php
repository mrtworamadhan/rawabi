<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts::public')] #[Title('Home - Rawabi ZamZam')] class extends Component {
    public $title = 'Home';
    public $search = '';

    public function mount($title = null)
    {
        $this->title = $title;
    }
};

?>

<div class="font-sans bg-slate-50 text-gray-800">
    

    <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-dark">
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?q=80&w=2070&auto=format&fit=crop" 
                 class="w-full h-full object-cover scale-105" 
                 alt="Mekkah Madinah">
            <div class="absolute inset-0 bg-gradient-to-b from-dark/80 via-dark/40 to-slate-50"></div>
        </div>

        <div class="container mx-auto px-4 relative z-10 pt-20">
            <div class="max-w-4xl mx-auto text-center">
                
                <div x-init="setTimeout(() => $el.classList.add('opacity-100', 'translate-y-0'), 100)"
                     class="opacity-0 -translate-y-4 transition duration-700 inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-4 py-2 rounded-full mb-6 border border-white/20">
                    <i class="bi bi-patch-check-fill text-primary"></i>
                    <span class="text-white text-xs md:text-sm font-semibold tracking-wide uppercase">Penyelenggara Umroh Resmi & Terpercaya</span>
                </div>

                <h1 x-init="setTimeout(() => $el.classList.add('opacity-100', 'translate-y-0'), 300)"
                    class="opacity-0 translate-y-8 transition duration-1000 text-5xl md:text-7xl lg:text-8xl font-extrabold text-white leading-[1.1] mb-6 tracking-tight">
                    Ibadah Nyaman <br>
                    <span class="text-primary italic font-arabic drop-shadow-lg">Hati Tenang</span>
                </h1>

                <p x-init="setTimeout(() => $el.classList.add('opacity-100'), 600)"
                   class="opacity-0 transition duration-1000 delay-500 text-gray-200 text-lg md:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
                    Wujudkan impian ke Baitullah bersama Rawabi Zamzam. Layanan profesional dengan fasilitas hotel bintang terbaik dan bimbingan sesuai Tuntunan Syari'at.
                </p>

                <div x-init="setTimeout(() => $el.classList.add('opacity-100', 'scale-100'), 900)"
                     class="opacity-0 scale-90 transition duration-700 flex flex-wrap justify-center gap-4">
                    
                    <a href="#paket" class="btn-gradient px-10 py-4 rounded-full text-lg flex items-center gap-3 animate-pulse-slow">
                        <i class="bi bi-calendar3"></i> Lihat Paket 2026
                    </a>
                    
                    <a href="#" class="bg-white/10 backdrop-blur-md border-2 border-white/30 text-white hover:bg-white hover:text-dark px-10 py-4 rounded-full text-lg font-bold transition-all flex items-center gap-2">
                        Pelajari Layanan
                    </a>
                </div>

                <div x-init="setTimeout(() => $el.classList.add('opacity-100', 'translate-y-0'), 1200)"
                     class="opacity-0 translate-y-10 transition duration-1000 mt-20 grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-3xl font-bold text-primary">5000+</div>
                        <div class="text-gray-400 text-xs uppercase tracking-widest">Jamaah</div>
                    </div>
                    <div class="p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-3xl font-bold text-primary">10+</div>
                        <div class="text-gray-400 text-xs uppercase tracking-widest">Tahun</div>
                    </div>
                    <div class="p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-3xl font-bold text-primary text-center">PPIU</div>
                        <div class="text-gray-400 text-xs uppercase tracking-widest text-center">Resmi</div>
                    </div>
                    <div class="p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-3xl font-bold text-primary">5★</div>
                        <div class="text-gray-400 text-xs uppercase tracking-widest">Hotel</div>
                    </div>
                </div>

            </div>
        </div>

        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-10 text-white/50 animate-bounce">
            <i class="bi bi-chevron-double-down text-2xl"></i>
        </div>
    </section>

    <section id="tentang" class="py-24 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                
                <div class="order-2 lg:order-1">
                    <span class="inline-block bg-primary/10 text-primary px-4 py-1.5 rounded-full text-xs font-bold tracking-widest uppercase mb-6">
                        Mengenal Rawabi Zamzam
                    </span>
                    <h2 class="text-4xl md:text-5xl font-extrabold text-dark mb-8 leading-tight font-sans">
                        Penyelenggara Umroh <br>
                        <span class="text-primary italic font-arabic drop-shadow-sm">Amanah & Profesional</span>
                    </h2>
                    
                    <div class="space-y-6 text-gray-600 text-lg leading-relaxed">
                        <p>
                            Berdiri dengan niat mulia untuk melayani tamu-tamu Allah, <span class="text-dark font-bold">Rawabi Zamzam</span> telah tumbuh menjadi biro perjalanan umroh yang mengedepankan kepastian dan kenyamanan.
                        </p>
                        <div class="space-y-6 text-gray-600 text-lg leading-relaxed">
                            <p>
                                Kami bukan sekadar agen perjalanan. <span class="text-dark font-semibold">Rawabi Zam Zam</span> adalah jembatan spiritual yang berdedikasi melayani umat muslim Indonesia menuju Baitullah dengan standar pelayanan tertinggi.
                            </p>
                            <p class="border-l-4 border-primary pl-6 italic bg-primary/5 py-4 rounded-r-xl">
                                "Menjadikan setiap perjalanan ibadah Anda sebagai momen transformasi spiritual yang nyaman, aman, dan penuh keberkahan."
                            </p>
                            <p>
                                Dengan pengalaman melayani ribuan jamaah, kami memahami bahwa Umroh adalah perjalanan hati. Oleh karena itu, setiap detail akomodasi dan bimbingan ibadah kami siapkan dengan teliti sesuai Tuntunan Syari'at, didampingi oleh Mutawwif berpengalaman.
                            </p>
                        </div>
                    </div>

                    

                    <div class="mt-12 flex justify-center md:justify-start">
                        <a href="/tentang-kami" wire:navigate class="group inline-flex items-center gap-3 bg-dark text-white px-8 py-4 rounded-2xl font-bold transition-all duration-300 hover:bg-primary hover:shadow-xl hover:shadow-primary/20">
                            Baca Selengkapnya
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                <i class="bi bi-arrow-right text-white transition-transform group-hover:translate-x-1"></i>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="order-1 lg:order-2 relative px-4">
                    <div
                        class="relative rounded-[3rem] overflow-hidden shadow-2xl transform lg:rotate-2 hover:rotate-0 transition-transform duration-700">
                        <img src="https://images.unsplash.com/photo-1565552130034-27e5a7cbd186?q=80&w=800" alt="Ibadah Umroh"
                            class="w-full h-[500px] object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark/60 via-transparent to-transparent"></div>
                    </div>
                
                    <div
                        class="absolute -top-6 -right-2 bg-white p-6 rounded-3xl shadow-xl flex items-center gap-4 animate-float border border-slate-100">
                        <div
                            class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg shadow-orange-500/30">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-dark leading-none">10+ Thn</p>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-tighter">Melayani Umroh</p>
                        </div>
                    </div>
                
                    <div
                        class="absolute -bottom-10 lg:left-0 bg-secondary p-5 rounded-3xl shadow-2xl flex items-center gap-4 animate-pulse-slow border border-white/20">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center text-white text-2xl">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="text-white">
                            <p class="text-2xl font-black leading-none">5000+</p>
                            <p class="text-[10px] opacity-80 font-bold uppercase tracking-widest">Jamaah Terdaftar</p>
                        </div>
                
                    </div>
                    
                </div>

            </div>
        </div>
    </section>

    <section class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center mb-20">
                <span class="inline-block bg-primary/10 text-primary px-5 py-2 rounded-full text-xs font-bold tracking-[0.2em] uppercase mb-4">
                    Keunggulan Kami
                </span>
                <h2 class="text-3xl md:text-5xl font-extrabold text-gray-900 leading-tight">
                    Keuntungan Beribadah Bersama <br class="hidden md:block">
                    <span class="text-gradient font-arabic">Rawabi Zam Zam</span>
                </h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $features = [
                        [
                            'icon' => 'bi-shield-check',
                            'title' => 'Terpercaya & Berizin',
                            'desc' => 'Resmi terdaftar di Kemenag RI dengan izin PPIU No. 05012300125230002.',
                            'delay' => '100'
                        ],
                        [
                            'icon' => 'bi-person-check',
                            'title' => 'Pembimbing Berpengalaman',
                            'desc' => 'Didampingi Ustadz berkompeten untuk memastikan ibadah sesuai tuntunan Syariat Nabi.',
                            'delay' => '200'
                        ],
                        [
                            'icon' => 'bi-building-check',
                            'title' => 'Akomodasi Premium',
                            'desc' => 'Hotel Bintang 4 & 5 dengan lokasi strategis, hanya selangkah ke pelataran Masjid.',
                            'delay' => '300'
                        ],
                        [
                            'icon' => 'bi-headset',
                            'title' => 'Layanan 24/7',
                            'desc' => 'Tim support responsif di Indonesia & Saudi siap membantu jamaah kapan saja.',
                            'delay' => '400'
                        ],
                    ];
                @endphp

                @foreach($features as $f)
                <div x-data="{ active: false }"
                     x-init="setTimeout(() => active = true, {{ $f['delay'] }})"
                     :class="active ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
                     class="group bg-slate-50 border border-slate-100 rounded-[2.5rem] p-8 transition-all duration-700 hover:bg-white hover:shadow-2xl hover:shadow-primary/10 hover:-translate-y-2 relative overflow-hidden">
                    
                    {{-- Decorative Circle on Hover --}}
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full transition-transform group-hover:scale-[3] duration-700"></div>

                    <div class="relative z-10">
                        {{-- Icon Box --}}
                        <div class="w-16 h-16 bg-white shadow-sm rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary group-hover:text-white transition-all duration-500 transform group-hover:rotate-[10deg]">
                            <i class="bi {{ $f['icon'] }} text-primary text-3xl group-hover:text-white transition-colors"></i>
                        </div>

                        {{-- Text Content --}}
                        <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition-colors">
                            {{ $f['title'] }}
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            {{ $f['desc'] }}
                        </p>
                    </div>

                    {{-- Bottom Accent Line --}}
                    <div class="absolute bottom-0 left-0 w-0 h-1 bg-gradient-to-r from-primary to-secondary transition-all duration-500 group-hover:w-full"></div>
                </div>
                @endforeach
            </div>

            <div class="mt-20 py-8 border-y border-gray-100 flex flex-wrap justify-center gap-x-12 gap-y-6 opacity-60 italic text-sm">
                <div class="flex items-center gap-2"><i class="bi bi-check-circle-fill text-green-500"></i> Pasti Berangkat</div>
                <div class="flex items-center gap-2"><i class="bi bi-check-circle-fill text-green-500"></i> Pasti Visa</div>
                <div class="flex items-center gap-2"><i class="bi bi-check-circle-fill text-green-500"></i> Pasti Hotel</div>
                <div class="flex items-center gap-2"><i class="bi bi-check-circle-fill text-green-500"></i> Pasti Jadwal</div>
            </div>
        </div>
    </section>

    <section id="layanan" class="py-24 bg-slate-50 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-secondary/5 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <span class="inline-block bg-primary/10 text-primary px-4 py-1.5 rounded-full text-sm font-bold tracking-widest uppercase mb-4 shadow-sm">
                    Program Unggulan 2026
                </span>
                <h2 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4">
                    Pilih Paket <span class="text-gradient">Sesuai Kebutuhan</span>
                </h2>
                <p class="text-gray-500 max-w-2xl mx-auto text-lg">
                    Berbagai pilihan paket umroh dengan fasilitas terbaik untuk memastikan kekhusyukan ibadah Anda.
                </p>
            </div>

            <div class="space-y-6 max-w-3xl mx-auto">
                @php 
                    $baseUrl = 'https://abuuurizal.github.io/rawabizamzam/';
                    $pakets = [
                        [
                            'nama' => 'Paket Ekonomis',
                            'harga' => '28 JT',
                            'durasi' => '9 Hari',
                            'hotel_m' => 'Hotel Makkah ★4',
                            'jarak_m' => '100 M ke Haram',
                            'hotel_n' => 'Hotel Madinah ★4',
                            'jarak_n' => '400 M ke Nabawi',
                            'maskapai' => ['Qatar_Airways_Logo.webp', 'etihad-airways.webp'],
                            'color' => 'from-primary to-orange-600',
                            'popular' => false
                        ],
                        [
                            'nama' => 'Paket Premium',
                            'harga' => '29.5 JT',
                            'durasi' => '9 Hari',
                            'hotel_m' => 'Hotel Makkah ★5',
                            'jarak_m' => 'Pelataran Masjid',
                            'hotel_n' => 'Hotel Madinah ★5',
                            'jarak_n' => '50 M ke Nabawi',
                            'maskapai' => ['Saudi-Arabian-Airlines-Logo.webp', 'etihad-airways.webp'],
                            'color' => 'from-secondary to-indigo-600',
                            'popular' => true
                        ],
                        [
                            'nama' => 'Paket Eksekutif',
                            'harga' => '35 JT',
                            'durasi' => '12 Hari',
                            'hotel_m' => 'Fairmont Tower ★5',
                            'jarak_m' => 'Depan Ka\'bah',
                            'hotel_n' => 'Anwar Movenpick ★5',
                            'jarak_n' => 'Pelataran Nabawi',
                            'maskapai' => ['Saudi-Arabian-Airlines-Logo.webp'],
                            'color' => 'from-dark to-slate-800',
                            'popular' => false
                        ]
                    ];
                @endphp
                
                @foreach($pakets as $paket)
                <div x-data="{ hover: false }" 
                     @mouseenter="hover = true" @mouseleave="hover = false"
                     class="group relative bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm transition-all duration-500 hover:shadow-2xl hover:-translate-y-2">
                    
                    @if($paket['popular'])
                    <div class="absolute top-0 right-10 z-20">
                        <div class="bg-secondary text-white text-[10px] font-bold px-4 py-1.5 rounded-b-xl shadow-lg animate-bounce">
                            PALING DIMINATI
                        </div>
                    </div>
                    @endif

                    <a href="#" class="flex items-stretch flex-col sm:flex-row">
                        <div class="relative bg-gradient-to-br {{ $paket['color'] }} p-8 flex flex-col items-center justify-center min-w-[160px] text-white text-center sm:rounded-r-[40px] transition-all duration-500 group-hover:scale-105">
                            <span class="text-[10px] opacity-80 uppercase tracking-[0.2em] font-bold mb-1">{{ $paket['nama'] }}</span>
                            <span class="text-4xl font-black tracking-tighter">{{ $paket['harga'] }}</span>
                            <span class="text-xs bg-white/20 px-3 py-1 rounded-full mt-2 font-medium">{{ $paket['durasi'] }}</span>
                        </div>

                        <div class="flex-1 p-6 flex flex-col justify-between">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 text-primary">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </div>
                                    <div>
                                        <p class="text-gray-900 font-extrabold text-sm">{{ $paket['hotel_m'] }}</p>
                                        <p class="text-gray-500 text-xs tracking-tight">{{ $paket['jarak_m'] }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-secondary/10 flex items-center justify-center flex-shrink-0 text-secondary">
                                        <i class="bi bi-geo-fill"></i>
                                    </div>
                                    <div>
                                        <p class="text-gray-900 font-extrabold text-sm">{{ $paket['hotel_n'] }}</p>
                                        <p class="text-gray-500 text-xs tracking-tight">{{ $paket['jarak_n'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-50 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    @foreach($paket['maskapai'] as $img)
                                    <div class="bg-gray-50 px-2 py-1 rounded-md border border-gray-100">
                                        <img src="{{$baseUrl}}assets/maskapai/{{$img}}" class="h-5 sm:h-7 w-auto object-contain opacity-70 group-hover:opacity-100 transition-opacity">
                                    </div>
                                    @endforeach
                                </div>
                                <div class="flex items-center text-primary font-bold text-sm group-hover:gap-2 transition-all">
                                    Detail <i class="bi bi-arrow-right ml-1"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            
            <div class="text-center mt-12">
                <a href="/paket-umroh" class="inline-flex items-center gap-3 text-gray-700 bg-white border border-gray-200 px-8 py-4 rounded-full font-bold shadow-sm hover:shadow-md hover:bg-primary hover:text-white hover:border-primary transition-all duration-300">
                    Eksplor Semua Jadwal Keberangkatan <i class="bi bi-grid-fill"></i>
                </a>
            </div>
        </div>
    </section>

    <section id="galeri" class="py-24 bg-slate-50 relative overflow-hidden border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
                <div class="max-w-xl">
                    <span class="text-primary font-bold tracking-[0.2em] uppercase text-xs">Momen Tak Terlupakan</span>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-dark mt-2">Dokumentasi <span class="text-gradient">Perjalanan Jamaah</span></h2>
                </div>
                <div class="flex gap-3">
                    <button class="w-12 h-12 rounded-full border border-gray-300 flex items-center justify-center text-gray-400 hover:bg-primary hover:text-white hover:border-primary transition-all shadow-sm">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="w-12 h-12 rounded-full border border-gray-300 flex items-center justify-center text-gray-400 hover:bg-primary hover:text-white hover:border-primary transition-all shadow-sm">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div x-data="{ lightboxOpen: false, currentImg: '' }" class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                @php $baseUrl = 'https://abuuurizal.github.io/rawabizamzam/'; @endphp
                
                @foreach(['jamaah-1.webp', 'jamaah-2.webp', 'jamaah-3.webp', 'jamaah-4.webp'] as $img)
                <div class="group relative aspect-square rounded-[2rem] overflow-hidden cursor-pointer shadow-md transition-all duration-500 hover:shadow-2xl"
                     @click="lightboxOpen = true; currentImg = '{{$baseUrl}}assets/galerry/{{$img}}'">
                    
                    <img src="{{$baseUrl}}assets/galerry/{{$img}}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                    
                    <div class="absolute inset-0 bg-primary/40 opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex items-center justify-center">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-primary shadow-xl scale-50 group-hover:scale-100 transition-transform duration-500">
                            <i class="bi bi-zoom-in text-xl"></i>
                        </div>
                    </div>
                </div>
                @endforeach

                <template x-teleport="body">
                    <div x-show="lightboxOpen" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center bg-dark/95 backdrop-blur-md p-4">
                        <button @click="lightboxOpen = false" class="absolute top-10 right-10 text-white text-4xl hover:text-primary transition-colors">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        <img :src="currentImg" class="max-w-full max-h-[85vh] rounded-3xl shadow-2xl border-4 border-white/10">
                    </div>
                </template>
            </div>

        </div>
    </section>
    <section id="testimoni" class="py-24 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center mb-16">
                <span class="inline-block bg-secondary/10 text-secondary px-5 py-2 rounded-full text-xs font-bold tracking-[0.2em] uppercase mb-4">
                    Viral di Media Sosial
                </span>
                <h2 class="text-3xl md:text-5xl font-extrabold text-dark leading-tight font-sans">
                    Cerita Jamaah di <span class="text-gradient font-arabic">TikTok & Instagram</span>
                </h2>
                <p class="text-gray-500 mt-4 max-w-xl mx-auto text-lg">
                    Lihat keseruan dan harunya perjalanan jamaah Rawabi Zamzam langsung dari postingan sosial media mereka.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <div class="bg-slate-50 p-4 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-500">
                    <div class="rounded-[2rem] overflow-hidden bg-white aspect-[9/16] relative shadow-inner">
                        {{-- URL akun --}}
                        <blockquote class="tiktok-embed" cite="https://www.tiktok.com/@v_rawabizamzam/video/7345678901234567890" data-video-id="7345678901234567890" style="max-width: 605px;min-width: 325px;" > 
                            <section> <a target="_blank" title="@v_rawabizamzam" href="https://www.tiktok.com/@v_rawabizamzam?refer=embed">@v_rawabizamzam</a> </section> 
                        </blockquote>
                    </div>
                    <div class="mt-4 px-4 pb-2 flex items-center justify-between text-xs text-gray-400 font-bold uppercase tracking-widest">
                        <span><i class="bi bi-tiktok text-dark"></i> TikTok Feed</span>
                        <i class="bi bi-arrow-up-right"></i>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-500 md:translate-y-10">
                    <div class="rounded-[2rem] overflow-hidden bg-white aspect-[9/16] relative shadow-inner flex items-center justify-center">
                        {{-- URL akun --}}
                        <iframe class="w-full h-full" src="https://www.instagram.com/reels/C4_v_abc123/embed/" frameborder="0" scrolling="no" allowtransparency="true"></iframe>
                    </div>
                    <div class="mt-4 px-4 pb-2 flex items-center justify-between text-xs text-gray-400 font-bold uppercase tracking-widest">
                        <span><i class="bi bi-instagram text-pink-600"></i> Instagram Reel</span>
                        <i class="bi bi-arrow-up-right"></i>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-500">
                    <div class="rounded-[2rem] overflow-hidden bg-white aspect-[9/16] relative shadow-inner">
                        <blockquote class="tiktok-embed" cite="https://www.tiktok.com/@v_rawabizamzam/video/0987654321098765432" data-video-id="0987654321098765432" style="max-width: 605px;min-width: 325px;" > 
                            <section> <a target="_blank" title="@v_rawabizamzam" href="https://www.tiktok.com/@v_rawabizamzam?refer=embed">@v_rawabizamzam</a> </section> 
                        </blockquote>
                    </div>
                    <div class="mt-4 px-4 pb-2 flex items-center justify-between text-xs text-gray-400 font-bold uppercase tracking-widest">
                        <span><i class="bi bi-tiktok text-dark"></i> TikTok Feed</span>
                        <i class="bi bi-arrow-up-right"></i>
                    </div>
                </div>

            </div>

            <div class="mt-24 text-center">
                <p class="text-gray-400 text-sm mb-4 uppercase tracking-[0.3em] font-bold">Follow Our Journey</p>
                <div class="flex justify-center gap-6">
                    <a href="#" class="btn-gradient px-8 py-3 rounded-full flex items-center gap-2">
                        <i class="bi bi-instagram"></i> Instagram
                    </a>
                    <a href="#" class="bg-dark text-white px-8 py-3 rounded-full flex items-center gap-2 hover:bg-black transition-all">
                        <i class="bi bi-tiktok"></i> TikTok
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative bg-gradient-to-br from-primary via-primary-hover to-orange-600 rounded-[3rem] p-8 md:p-16 overflow-hidden shadow-2xl shadow-primary/20">
                
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-black/5 rounded-full translate-y-1/2 -translate-x-1/2 blur-2xl"></div>

                <div class="relative z-10 grid lg:grid-cols-2 gap-12 items-center">
                    <div class="text-center lg:text-left">
                        <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-6 leading-tight font-sans">
                            Siap Mewujudkan Impian <br>
                            <span class="text-dark/80 italic font-arabic">Ke Tanah Suci?</span>
                        </h2>
                        <p class="text-white/90 text-lg mb-8 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                            Jangan tunda niat suci Anda. Daftar sekarang dan amankan seat perjalanan ibadah terbaik bersama Rawabi Zamzam. 
                        </p>
                        
                        <div class="flex flex-wrap justify-center lg:justify-start gap-6 text-white/80 text-sm font-bold tracking-widest uppercase">
                            <div class="flex items-center gap-2"><i class="bi bi-shield-check-fill"></i> Resmi Berizin</div>
                            <div class="flex items-center gap-2"><i class="bi bi-person-heart"></i> 5000+ Jamaah</div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-end">
                        <a href="#" class="bg-white text-primary px-10 py-5 rounded-full font-black text-lg shadow-xl hover:bg-dark hover:text-white transition-all duration-500 flex items-center justify-center gap-3">
                            <i class="bi bi-person-plus-fill"></i> Daftar Sekarang
                        </a>
                        <a href="https://wa.me/6281234567890" target="_blank" class="bg-dark text-white px-10 py-5 rounded-full font-black text-lg shadow-xl hover:bg-white hover:text-dark transition-all duration-500 flex items-center justify-center gap-3 border border-white/10">
                            <i class="bi bi-whatsapp"></i> Konsultasi Gratis
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="artikel" class="py-24 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-col md:flex-row justify-between items-center md:items-end mb-16 gap-8">
    
                <div class="max-w-xl text-center md:text-left mx-auto md:mx-0">
                    <span class="inline-block bg-secondary/10 text-secondary px-4 py-1.5 rounded-full text-xs font-bold tracking-widest uppercase mb-4">
                        Info & Edukasi
                    </span>
                    <h2 class="text-3xl md:text-5xl font-extrabold text-dark leading-tight">
                        Wawasan Seputar <br>
                        <span class="text-gradient font-arabic">Ibadah Umroh</span>
                    </h2>
                </div>

                <div class="flex-shrink-0">
                    <a href="#" class="inline-flex items-center gap-2 text-dark font-bold hover:text-primary transition-colors py-3 px-8 rounded-full border border-gray-100 shadow-sm bg-white md:bg-slate-50 hover:bg-white">
                        Lihat Semua Artikel <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
                @php
                    $articles = [
                        [
                            'category' => 'Tips Umroh',
                            'title' => 'Perlengkapan Umroh Pria & Wanita yang Wajib Dibawa',
                            'desc' => 'Panduan lengkap barang bawaan agar ibadah Anda tenang dan nyaman selama di Tanah Suci.',
                            'image' => 'https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?q=80&w=600',
                            'date' => '10 Feb 2026'
                        ],
                        [
                            'category' => 'Panduan Ibadah',
                            'title' => 'Tata Cara Thawaf dan Doa yang Dianjurkan',
                            'desc' => 'Pelajari rukun umroh paling utama agar sesuai dengan Tuntunan Syariat Nabi Muhammad SAW.',
                            'image' => 'https://images.unsplash.com/photo-1565552130034-27e5a7cbd186?q=80&w=600',
                            'date' => '08 Feb 2026'
                        ],
                        [
                            'category' => 'Kabar Rawabi',
                            'title' => 'Hotel Makkah & Madinah Terbaru di Paket 2026',
                            'desc' => 'Intip fasilitas akomodasi premium yang siap menyambut kepulangan Anda di Baitullah.',
                            'image' => 'https://images.unsplash.com/photo-1548543604-a87c9909afce?q=80&w=600',
                            'date' => '05 Feb 2026'
                        ]
                    ];
                @endphp

                @foreach($articles as $article)
                <article class="group bg-white rounded-[2.5rem] overflow-hidden border border-gray-100 shadow-sm hover:shadow-2xl hover:shadow-secondary/10 transition-all duration-500 flex flex-col">
                    <div class="relative aspect-[16/10] overflow-hidden">
                        <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}" 
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        <div class="absolute top-4 left-4">
                            <span class="bg-white/90 backdrop-blur-md text-secondary text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest shadow-sm">
                                {{ $article['category'] }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-8 flex flex-col flex-1">
                        <span class="text-xs text-gray-400 font-bold mb-3 flex items-center gap-2">
                            <i class="bi bi-calendar3"></i> {{ $article['date'] }}
                        </span>
                        <h3 class="text-xl font-bold text-dark mb-4 leading-snug group-hover:text-primary transition-colors">
                            <a href="#">{{ $article['title'] }}</a>
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed mb-6 line-clamp-2">
                            {{ $article['desc'] }}
                        </p>
                        <div class="mt-auto">
                            <a href="#" class="inline-flex items-center gap-2 text-dark font-black text-xs uppercase tracking-widest group-hover:text-primary transition-all">
                                Selengkapnya <i class="bi bi-arrow-right transition-transform group-hover:translate-x-2"></i>
                            </a>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>

        </div>
    </section>
    
    <section id="tagline" class="py-20 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            
            <div class="max-w-3xl mx-auto mb-12">
                <h2 class="text-3xl md:text-5xl font-extrabold text-dark mb-6 leading-tight">
                    Melayani Setulus Hati, Mengantar <br>
                    <span class="text-primary italic font-arabic drop-shadow-sm">Ibadah yang Mabrur</span>
                </h2>
                <p class="text-gray-500 text-lg leading-relaxed">
                    Kami hadir sebagai mitra perjalanan spiritual Anda dengan izin resmi sebagai 
                    <span class="text-dark font-bold">Penyelenggara Perjalanan Ibadah Umroh (PPIU)</span>. 
                    Keamanan, kenyamanan, dan kepastian keberangkatan adalah prioritas utama kami.
                </p>
            </div>

            <div class="inline-flex flex-col md:flex-row items-center gap-4 bg-primary/5 border border-primary/10 px-8 py-4 rounded-3xl mb-16 shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="bi bi-shield-fill-check text-primary text-2xl"></i>
                    <div class="text-left">
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold leading-none">Izin Resmi PPIU</p>
                        <p class="text-gray-800 font-extrabold tracking-tight">No. 05012300125230002</p>
                    </div>
                </div>
                <div class="hidden md:block h-8 w-[1px] bg-primary/20 mx-4"></div>
                <div class="flex items-center gap-2">
                    <span class="bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded">TERAKREDITASI A</span>
                    <span class="text-gray-600 text-sm font-medium italic">Kementerian Agama RI</span>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-12 relative">
                <p class="text-xs text-gray-400 uppercase tracking-[0.3em] font-bold mb-10">
                    Didukung & Terdaftar Resmi Oleh
                </p>

                <div class="relative group">
                    <div class="absolute left-0 top-0 bottom-0 w-24 bg-gradient-to-r from-white to-transparent z-10"></div>
                    <div class="absolute right-0 top-0 bottom-0 w-24 bg-gradient-to-l from-white to-transparent z-10"></div>

                    <div class="overflow-hidden py-4">
                        <div class="partner-scroll flex items-center gap-12 md:gap-20">
                            @php 
                                $baseUrl = 'https://abuuurizal.github.io/rawabizamzam/';
                                $partners = [
                                    ['img' => 'assets/partners/logo-kemenag.webp', 'alt' => 'Kemenag RI'],
                                    ['img' => 'assets/partners/siskopatuh-logo.webp', 'alt' => 'SISKO PATUH'],
                                    ['img' => 'assets/partners/kan-logo.webp', 'alt' => 'KAN'],
                                    ['img' => 'assets/partners/amphuri-logo.webp', 'alt' => 'AMPHURI'],
                                    ['img' => 'assets/partners/STA-logo.webp', 'alt' => 'Saudi Tourism'],
                                    ['img' => 'assets/partners/iata-logo.webp', 'alt' => 'IATA'],
                                ];
                            @endphp
                            
                            @foreach($partners as $p)
                                <img src="{{$baseUrl . $p['img']}}" alt="{{$p['alt']}}" 
                                     class="h-12 md:h-16 w-auto grayscale opacity-60 hover:grayscale-0 hover:opacity-100 transition-all duration-500 flex-shrink-0">
                            @endforeach
                            
                            @foreach($partners as $p)
                                <img src="{{$baseUrl . $p['img']}}" alt="{{$p['alt']}}" 
                                     class="h-12 md:h-16 w-auto grayscale opacity-60 hover:grayscale-0 hover:opacity-100 transition-all duration-500 flex-shrink-0">
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    
</div>
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts::public')] #[Title('Paket Umroh 2026 - Rawabi Zamzam')] class extends Component
{
    public function getPakets()
    {
        return [
            [
                'id' => 1,
                'nama' => 'Umroh Ekonomis Syawal',
                'harga' => '28.500.000',
                'poster' => 'https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?q=80&w=600',
                'hotel_m' => 'Hotel Makkah ★4',
                'hotel_n' => 'Hotel Madinah ★4',
                'pesawat' => 'Qatar Airways',
                'durasi' => '9 Hari',
                'badge' => 'Hemat',
                'itinerary' => [
                    'Hari 1: Berkumpul di Bandara Soekarno Hatta & Keberangkatan',
                    'Hari 2: Tiba di Madinah & Check-in Hotel',
                    'Hari 3: Ziarah Raudhah & Makam Rasulullah',
                    'Hari 4: City Tour Madinah & Masjid Quba',
                    'Hari 5: Menuju Makkah & Ambil Miqat Umroh Pertama',
                    'Hari 6: Ibadah Mandiri & Memperbanyak Tawaf',
                    'Hari 7: City Tour Makkah & Miqat Kedua (Jironah)',
                    'Hari 8: Tawaf Wada & Menuju Jeddah',
                    'Hari 9: Tiba di Indonesia'
                ]
            ],
            [
                'id' => 2,
                'nama' => 'Umroh Premium Ramadhan',
                'harga' => '32.900.000',
                'poster' => 'https://images.unsplash.com/photo-1565552130034-27e5a7cbd186?q=80&w=600',
                'hotel_m' => 'Pullman Zamzam ★5',
                'hotel_n' => 'Frontel Al Harithia ★5',
                'pesawat' => 'Saudi Arabian Airlines (Direct)',
                'durasi' => '9 Hari',
                'badge' => 'Terpopuler',
                'itinerary' => [
                    'Hari 1: Keberangkatan Langsung Madinah (Direct Flight)',
                    'Hari 2: Tiba di Madinah, Istirahat & Ibadah Mandiri',
                    'Hari 3: Ziarah Khusus Raudhah (Sesuai Tasreh)',
                    'Hari 4: City Tour Madinah & Museum Wahyu',
                    'Hari 5: Manasik Ulang & Menuju Makkah (Kereta Cepat)',
                    'Hari 6: Umroh Pertama & Istirahat',
                    'Hari 7: City Tour Makkah & Miqat Kedua',
                    'Hari 8: Tawaf Wada & City Tour Jeddah',
                    'Hari 9: Tiba di Indonesia'
                ]
            ],
            [
                'id' => 3,
                'nama' => 'Umroh Eksekutif VIP',
                'harga' => '45.000.000',
                'poster' => 'https://images.unsplash.com/photo-1548543604-a87c9909afce?q=80&w=600',
                'hotel_m' => 'Fairmont Clock Tower ★5 VIP',
                'hotel_n' => 'Anwar Movenpick ★5 VIP',
                'pesawat' => 'Emirates (Business Class)',
                'durasi' => '12 Hari',
                'badge' => 'Eksklusif',
                'itinerary' => [
                    'Hari 1: Keberangkatan Menuju Dubai (Transit)',
                    'Hari 2: City Tour Dubai & Menuju Madinah',
                    'Hari 3: Tiba di Madinah & Check-in Hotel Pelataran',
                    'Hari 4: Ziarah Raudhah Eksklusif',
                    'Hari 5: City Tour Madinah & Kebun Kurma',
                    'Hari 6: Menuju Makkah Menggunakan Kereta Cepat (VIP)',
                    'Hari 7: Ibadah Umroh Pertama',
                    'Hari 8: Ziarah Makkah & Miqat Kedua',
                    'Hari 9: Ziarah Thaif (Wisata Kuliner & Sejarah)',
                    'Hari 10: Memperbanyak Ibadah di Masjidil Haram',
                    'Hari 11: Tawaf Wada & Menuju Bandara',
                    'Hari 12: Tiba di Indonesia'
                ]
            ],
        ];
    }
};
?>

<div class="public-page">
    <section class="bg-dark py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-80 pointer-events-none"
            style="
                background-image: url('http://www.transparenttextures.com/patterns/arabesque.png');
                background-repeat: repeat;
                background-size: 200px 200px;
                filter: brightness(0.5) sepia(1) hue-rotate(10deg) saturate(5);
            ">
        </div>

        <div class="container mx-auto px-4 relative z-10 text-center pt-20">
            <div x-init="setTimeout(() => $el.classList.add('opacity-100', 'translate-y-0'), 100)"
                class="opacity-0 translate-y-4 transition duration-700">
                <span class="inline-block bg-primary/20 backdrop-blur-md text-primary px-4 py-1 rounded-full text-xs font-bold tracking-widest uppercase mb-4 border border-primary/30">
                    Pilihan Paket Terbaik
                </span>
                <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-4 font-sans tracking-tight">
                    Jadwal Keberangkatan <br>
                    <span class="text-primary italic font-arabic drop-shadow-lg text-5xl md:text-7xl">Umroh 2026</span>
                </h1>
                <p class="text-gray-200 mt-6 max-w-2xl mx-auto text-base md:text-lg opacity-90 leading-relaxed">
                    Pilih paket yang sesuai dengan kebutuhan Anda. Semua layanan kami dirancang untuk memberikan ketenangan dalam beribadah.
                </p>
            </div>
        </div>
    </section>

    <section class="py-16 bg-slate-50 min-h-screen" x-data="{ openModal: false, selectedPaket: {} }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid md:grid-cols-3 gap-8">
                @foreach($this->getPakets() as $paket)
                <div class="bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-gray-100 flex flex-col hover:shadow-2xl transition-all duration-500 group">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="{{ $paket['poster'] }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute top-4 right-4 bg-primary text-white text-[10px] font-bold px-3 py-1.5 rounded-full shadow-lg">
                            {{ $paket['badge'] }}
                        </div>
                    </div>

                    <div class="p-8 flex flex-1 flex-col">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-dark leading-tight">{{ $paket['nama'] }}</h3>
                            <span class="text-primary font-black text-lg">Rp {{ $paket['harga'] }}</span>
                        </div>

                        <div class="space-y-3 mb-8">
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <i class="bi bi-clock-history text-primary"></i> {{ $paket['durasi'] }}
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <i class="bi bi-building text-primary"></i> {{ $paket['hotel_m'] }}
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <i class="bi bi-airplane-engines text-primary"></i> {{ $paket['pesawat'] }}
                            </div>
                        </div>

                        <div class="mt-auto grid grid-cols-2 gap-3">
                            <button @click="openModal = true; selectedPaket = @js($paket)" 
                                    class="bg-slate-100 text-dark font-bold py-3 rounded-xl hover:bg-slate-200 transition-colors text-sm">
                                Detail Paket
                            </button>
                            <a href="https://wa.me/6281234567890" class="btn-gradient text-center py-3 rounded-xl text-sm flex items-center justify-center gap-2">
                                <i class="bi bi-whatsapp"></i> Daftar
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <template x-teleport="body">
            <div x-show="openModal" 
                 class="fixed inset-0 z-[9999] flex items-center justify-center p-4 md:p-8"
                 x-cloak>
                <div x-show="openModal" x-transition.opacity @click="openModal = false" class="absolute inset-0 bg-dark/90 backdrop-blur-sm"></div>

                <div x-show="openModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-10"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     class="bg-white w-full max-w-4xl max-h-[90vh] rounded-[2rem] overflow-hidden relative z-10 flex flex-col md:flex-row shadow-2xl">
                    
                    <div class="md:w-1/3 bg-slate-100 relative">
                        <img :src="selectedPaket.poster" class="w-full h-48 md:h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark/80 to-transparent flex flex-col justify-end p-8 text-white">
                            <h2 class="text-2xl font-bold" x-text="selectedPaket.nama"></h2>
                            <p class="text-primary font-black text-xl mt-2" x-text="'Rp ' + selectedPaket.harga"></p>
                        </div>
                        <button @click="openModal = false" class="absolute top-4 right-4 md:left-4 md:right-auto bg-white/20 backdrop-blur-md text-white w-10 h-10 rounded-full flex items-center justify-center hover:bg-white hover:text-dark transition-all">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="md:w-2/3 p-8 md:p-12 overflow-y-auto" x-data="{ tab: 'itinerary' }">
                        <div class="flex gap-8 border-b border-gray-100 mb-8">
                            <button @click="tab = 'itinerary'" :class="tab === 'itinerary' ? 'text-primary border-b-2 border-primary' : 'text-gray-400'" class="pb-4 font-bold uppercase tracking-widest text-xs transition-all">Itinerary</button>
                            <button @click="tab = 'fasilitas'" :class="tab === 'fasilitas' ? 'text-primary border-b-2 border-primary' : 'text-gray-400'" class="pb-4 font-bold uppercase tracking-widest text-xs transition-all">Fasilitas</button>
                        </div>

                        <div x-show="tab === 'itinerary'" x-transition>
                            <div class="space-y-6">
                                <template x-for="(step, index) in selectedPaket.itinerary" :key="index">
                                    <div class="flex gap-4">
                                        <div class="flex flex-col items-center">
                                            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold border border-primary/20" x-text="index + 1"></div>
                                            <div class="w-[1px] h-full bg-gray-100 mt-2"></div>
                                        </div>
                                        <p class="text-gray-600 text-sm pt-1" x-text="step"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div x-show="tab === 'fasilitas'" x-transition>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <li class="flex items-center gap-3 text-sm text-gray-600"><i class="bi bi-check2-circle text-green-500"></i> Tiket Pesawat PP</li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><i class="bi bi-check2-circle text-green-500"></i> Visa Umroh</li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><i class="bi bi-check2-circle text-green-500"></i> Manasik Umroh</li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><i class="bi bi-check2-circle text-green-500"></i> Muthawwif Profesional</li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><i class="bi bi-check2-circle text-green-500"></i> Bus AC Terbaru</li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><i class="bi bi-check2-circle text-green-500"></i> Zam-zam 5 Liter</li>
                            </ul>
                        </div>

                        <div class="mt-12 flex justify-end">
                             <a href="https://wa.me/6281234567890" class="btn-gradient px-8 py-4 rounded-2xl flex items-center gap-2">
                                <i class="bi bi-whatsapp"></i> Pesan Seat Sekarang
                             </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </section>
</div>
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts::public')] #[Title('Tentang Kami - Rawabi Zamzam')] class extends Component
{
    //
};
?>

<div class="public-page">
    <section class="relative bg-dark py-24 overflow-hidden">
        <div class="absolute inset-0 opacity-80 pointer-events-none"
            style="
                background-image: url('http://www.transparenttextures.com/patterns/arabesque.png');
                background-repeat: repeat;
                background-size: 200px 200px;
                filter: brightness(0.5) sepia(1) hue-rotate(10deg) saturate(5);
            ">
        </div>
        <div class="container mx-auto px-4 relative z-10 text-center pt-10">
            <div x-init="setTimeout(() => $el.classList.add('opacity-100', 'translate-y-0'), 100)"
                 class="opacity-0 translate-y-4 transition duration-700">
                <span class="inline-block bg-secondary/20 backdrop-blur-md text-secondary px-4 py-1 rounded-full text-xs font-bold tracking-widest uppercase mb-4 border border-secondary/30">
                    Mengenal Lebih Dekat
                </span>
                <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-4 font-sans tracking-tight">
                    Dedikasi Pelayanan <br>
                    <span class="text-primary italic font-arabic drop-shadow-lg text-5xl md:text-7xl">Rawabi Zamzam</span>
                </h1>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-20 items-center">
                <div class="relative">
                    <div class="relative rounded-[3rem] overflow-hidden shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1565552130034-27e5a7cbd186?q=80&w=800" class="w-full h-[500px] object-cover" alt="Visi Misi">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark/60 to-transparent"></div>
                    </div>
                    <div class="absolute -bottom-10 -right-5 bg-primary p-8 rounded-3xl shadow-2xl text-white max-w-xs animate-float">
                        <i class="bi bi-quote text-4xl opacity-50"></i>
                        <p class="font-medium italic">"Melayani Tamu Allah adalah kehormatan tertinggi bagi kami."</p>
                    </div>
                </div>

                <div>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-dark mb-10 leading-tight">
                        Visi Kami Menjadi <br>
                        <span class="text-gradient font-arabic text-4xl">Pilar Ibadah Anda</span>
                    </h2>
                    
                    <div class="space-y-8">
                        <div class="flex gap-6">
                            <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-xl font-bold">1</div>
                            <div>
                                <h4 class="text-xl font-bold text-dark mb-2">Visi Utama</h4>
                                <p class="text-gray-600 leading-relaxed">Menjadi penyelenggara perjalanan ibadah Umroh dan Haji yang terkemuka di Indonesia dengan standar pelayanan internasional namun tetap memegang teguh nilai-nilai Sunnah.</p>
                            </div>
                        </div>
                        <div class="flex gap-6">
                            <div class="flex-shrink-0 w-12 h-12 bg-secondary/10 rounded-2xl flex items-center justify-center text-secondary text-xl font-bold">2</div>
                            <div>
                                <h4 class="text-xl font-bold text-dark mb-2">Misi Pelayanan</h4>
                                <ul class="text-gray-600 space-y-2 list-disc list-inside">
                                    <li>Memberikan kepastian jadwal keberangkatan.</li>
                                    <li>Menyediakan akomodasi terbaik dengan akses termudah ke Masjid.</li>
                                    <li>Membina jamaah agar mencapai ibadah yang mabrur melalui manasik intensif.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-slate-50 border-y border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-dark">Legalitas & Sertifikasi Resmi</h2>
                <p class="text-gray-500 mt-4">Keamanan Anda adalah prioritas kami. Kami terdaftar secara resmi di berbagai otoritas terkait.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-[2rem] border border-gray-200 shadow-sm hover:shadow-xl transition-all group">
                    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-green-600 text-3xl mb-6 group-hover:bg-green-600 group-hover:text-white transition-all">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">Izin PPIU</h3>
                    <p class="text-gray-500 text-sm mb-4">Terdaftar resmi di Kemenag RI sebagai Penyelenggara Perjalanan Ibadah Umroh.</p>
                    <span class="text-dark font-black tracking-wider bg-slate-100 px-3 py-1 rounded-lg text-xs">05012300125230002</span>
                </div>

                <div class="bg-white p-8 rounded-[2rem] border border-gray-200 shadow-sm hover:shadow-xl transition-all group">
                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-3xl mb-6 group-hover:bg-blue-600 group-hover:text-white transition-all">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">Sertifikasi IATA</h3>
                    <p class="text-gray-500 text-sm mb-4">Anggota resmi International Air Transport Association untuk layanan tiket internasional.</p>
                    <span class="text-dark font-black tracking-wider bg-slate-100 px-3 py-1 rounded-lg text-xs">MEMBER OF IATA</span>
                </div>

                <div class="bg-white p-8 rounded-[2rem] border border-gray-200 shadow-sm hover:shadow-xl transition-all group">
                    <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600 text-3xl mb-6 group-hover:bg-orange-600 group-hover:text-white transition-all">
                        <i class="bi bi-building"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">Anggota AMPHURI</h3>
                    <p class="text-gray-500 text-sm mb-4">Anggota aktif Asosiasi Muslim Penyelenggara Haji dan Umroh Republik Indonesia.</p>
                    <span class="text-dark font-black tracking-wider bg-slate-100 px-3 py-1 rounded-lg text-xs">REG. NO: 123/2024</span>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-dark">Manajemen Rawabi Zamzam</h2>
                <p class="text-gray-500 mt-4">Tim profesional yang siap melayani kebutuhan ibadah Anda 24/7.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @for ($i = 1; $i <= 4; $i++)
                <div class="text-center group">
                    <div class="relative w-40 h-40 mx-auto mb-6">
                        <div class="absolute inset-0 bg-primary/20 rounded-full scale-110 group-hover:scale-125 transition-transform duration-500"></div>
                        <img src="https://ui-avatars.com/api/?name=Team+Member+{{$i}}&background=random" class="relative w-full h-full object-cover rounded-full shadow-lg border-4 border-white">
                    </div>
                    <h4 class="text-lg font-bold text-dark">Nama Direksi {{$i}}</h4>
                    <p class="text-primary text-sm font-bold uppercase tracking-widest mt-1">Jabatan Strategis</p>
                </div>
                @endfor
            </div>
        </div>
    </section>
</div>
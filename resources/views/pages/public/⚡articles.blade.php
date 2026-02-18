<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts::public')] #[Title('Informasi & Kabar Umroh - Rawabi Zamzam')] class extends Component
{
    public function getArticles()
    {
        return [
            [
                'id' => 1,
                'slug' => 'tips-perlengkapan-umroh',
                'category' => 'Tips Umroh',
                'title' => 'Panduan Lengkap Perlengkapan Umroh Pria & Wanita 2026',
                'excerpt' => 'Bingung bawa apa saja ke Tanah Suci? Simak daftar barang wajib agar ibadah Anda tetap fokus dan nyaman.',
                'image' => 'https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?q=80&w=600',
                'date' => '10 Feb 2026',
                'author' => 'Ustadz Ahmad'
            ],
            [
                'id' => 2,
                'slug' => 'keutamaan-umroh-ramadhan',
                'category' => 'Wawasan Islam',
                'title' => 'Keutamaan Menunaikan Ibadah Umroh di Bulan Ramadhan',
                'excerpt' => 'Umroh di bulan Ramadhan pahalanya setara dengan haji bersama Rasulullah. Benarkah demikian? Simak penjelasannya.',
                'image' => 'https://images.unsplash.com/photo-1565552130034-27e5a7cbd186?q=80&w=600',
                'date' => '08 Feb 2026',
                'author' => 'Tim Media Rawabi'
            ],
            [
                'id' => 3,
                'slug' => 'rute-city-tour-thaif',
                'category' => 'Destinasi',
                'title' => 'Menikmati Sejuknya Kota Thaif: Destinasi Favorit Jamaah',
                'excerpt' => 'Selain Mekkah dan Madinah, Thaif menjadi destinasi yang tak boleh dilewatkan. Ada apa saja di sana?',
                'image' => 'https://images.unsplash.com/photo-1548543604-a87c9909afce?q=80&w=600',
                'date' => '05 Feb 2026',
                'author' => 'Admin Rawabi'
            ],
        ];
    }
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
                <span class="inline-block bg-primary/20 backdrop-blur-md text-primary px-4 py-1.5 rounded-full text-xs font-bold tracking-widest uppercase mb-4 border border-primary/30">
                    Artikel & Edukasi
                </span>
                <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-4 font-sans tracking-tight">
                    Kabar & <span class="text-primary italic font-arabic drop-shadow-lg">Info Islam</span>
                </h1>
                <p class="text-gray-300 mt-6 max-w-2xl mx-auto text-lg">
                    Temukan panduan ibadah, tips perjalanan, dan berita terbaru seputar dunia umroh dan haji.
                </p>
            </div>
        </div>
    </section>

    <section class="py-20 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
                @foreach($this->getArticles() as $article)
                <article class="group bg-white rounded-[2.5rem] overflow-hidden border border-gray-100 shadow-sm hover:shadow-2xl hover:shadow-primary/5 transition-all duration-500 flex flex-col">
                    
                    <div class="relative aspect-[16/10] overflow-hidden">
                        <img src="{{ $article['image'] }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        <div class="absolute top-4 left-4">
                            <span class="bg-dark/80 backdrop-blur-md text-primary text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest">
                                {{ $article['category'] }}
                            </span>
                        </div>
                    </div>

                    <div class="p-8 flex flex-col flex-1">
                        <div class="flex items-center gap-4 text-xs text-gray-400 font-semibold mb-4">
                            <span class="flex items-center gap-1.5"><i class="bi bi-person-circle"></i> {{ $article['author'] }}</span>
                            <span class="flex items-center gap-1.5"><i class="bi bi-calendar3"></i> {{ $article['date'] }}</span>
                        </div>
                        
                        <h3 class="text-xl font-bold text-dark mb-4 leading-snug group-hover:text-primary transition-colors">
                            <a href="/artikel/{{ $article['slug'] }}" wire:navigate>{{ $article['title'] }}</a>
                        </h3>
                        
                        <p class="text-gray-500 text-sm leading-relaxed mb-8 line-clamp-3">
                            {{ $article['excerpt'] }}
                        </p>

                        <div class="mt-auto pt-6 border-t border-gray-50">
                            <a href="/artikel/{{ $article['slug'] }}" wire:navigate 
                               class="group/btn inline-flex items-center gap-2 text-dark font-black text-xs uppercase tracking-widest hover:text-primary transition-all">
                                Baca Selengkapnya 
                                <i class="bi bi-arrow-right bg-slate-100 w-8 h-8 flex items-center justify-center rounded-full transition-all group-hover/btn:bg-primary group-hover/btn:text-white group-hover/btn:translate-x-1"></i>
                            </a>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>

            <div class="mt-20 flex justify-center">
                <nav class="flex gap-2">
                    <a href="#" class="w-12 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-dark font-bold hover:bg-primary hover:text-white transition-all shadow-sm">1</a>
                    <a href="#" class="w-12 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-dark font-bold hover:bg-primary hover:text-white transition-all shadow-sm">2</a>
                    <a href="#" class="w-12 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-dark font-bold hover:bg-primary hover:text-white transition-all shadow-sm">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </nav>
            </div>

        </div>
    </section>

    <section class="py-20 bg-dark relative overflow-hidden">
        <div class="absolute inset-0 opacity-15" 
            style="background-image: url('https://www.transparenttextures.com/patterns/diagmonds-light.png');
                background-repeat: repeat;
                background-size: 200px 200px;
            ">
        
        </div>
        <div class="max-w-4xl mx-auto px-4 relative z-10 text-center text-white">
            <h2 class="text-3xl font-bold mb-4">Dapatkan Info Promo & Tips Umroh</h2>
            <p class="text-gray-400 mb-8">Berlangganan buletin kami untuk mendapatkan update langsung ke email Anda.</p>
            <form class="flex flex-col sm:flex-row gap-3">
                <input type="email" placeholder="Alamat Email Anda" class="flex-1 px-8 py-4 rounded-full bg-white/10 border border-white/20 focus:outline-none focus:border-primary text-white">
                <button type="submit" class="btn-gradient px-10 py-4 rounded-full">Berlangganan</button>
            </form>
        </div>
    </section>
</div>
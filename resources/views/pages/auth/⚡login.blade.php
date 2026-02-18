<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts::auth')] #[Title('Login - Rawabi ZamZam')] class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login()
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah bro.',
            ]);
        }

        session()->regenerate();

        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            return redirect()->intended('/admin');
        }
        
        if ($user->hasRole('owner') || $user->hasRole('bod')) {
            return redirect()->route('dashboard.executive');
        }

        if ($user->hasRole('finance')) {
            return redirect()->route('finance.pos');
        }

        if ($user->hasRole('marketing')) {
            return redirect()->route('marketing.salesApp');
        }

        if ($user->hasRole('operasional') || $user->hasRole('staff_ops')) {
            return redirect()->route('operations.dashboard');
        }

        if ($user->hasRole('media') || $user->hasRole('editor')) {
            return redirect()->route('media.studio');
        }

        return redirect()->intended('/');
    }
};
?>

<div class="min-h-screen bg-slate-100 flex items-center justify-center relative overflow-hidden p-4">
    
    <!-- <div class="absolute -top-20 -left-20 w-80 h-80 opacity-10 dark:opacity-15 pointer-events-none transform -rotate-12">
        <img src="{{ asset('images/icons/masjid1.png') }}" alt="Masjid Decoration" class="w-full h-full object-contain">
    </div> -->

    <div class="absolute -bottom-24 -right-24 w-96 h-96 opacity-40 dark:opacity-40 pointer-events-none transform">
        <img src="{{ asset('images/icons/kabah1.png') }}" alt="Kabah Decoration" class="w-full h-full object-contain">
    </div>

    <div class="absolute top-0 left-0 w-96 h-96 bg-primary/10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-secondary/10 rounded-full translate-x-1/2 translate-y-1/2 blur-3xl"></div>

    <div class="absolute inset-0 opacity-[0.02] pointer-events-none" style="background-image: url('https://www.transparenttextures.com/patterns/islamic-art.png');"></div>

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-[2rem] shadow-xl mb-4 border border-slate-100 overflow-hidden">
                <img src="{{ asset('images/logo/logo.jpg') }}" alt="Logo" class="w-full h-full object-cover">
            </div>
            <h1 class="text-2xl font-black text-dark tracking-tight">RAWABI SYSTEM</h1>
            <p class="text-gray-500 text-sm">Command Center Access</p>
            <p class="text-gray-500 text-xs">V 1.0</p>
        </div>

        <div class="bg-white/80 backdrop-blur-xl border border-white rounded-[3rem] shadow-2xl shadow-dark/5 relative overflow-hidden">
    
            <div class="absolute -top-24 -left-48 w-96 h-96 opacity-15 pointer-events-none z-0">
                <img src="{{ asset('images/ornaments/ornamen1.png') }}" 
                    alt="Ornamen" 
                    class="w-full h-full object-contain transform rotate-90">
            </div>

            <div class="p-8 md:p-10 relative z-10">
                <form wire:submit="login" class="space-y-6">
                    {{-- Input Email --}}
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-widest ml-1">Email Address</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="bi bi-envelope text-gray-400 group-focus-within:text-primary transition-colors"></i>
                            </div>
                            <input wire:model="email" type="email" required
                                class="block w-full pl-11 pr-4 py-4 bg-slate-100/50 border-transparent rounded-2xl focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none text-sm font-medium" 
                                placeholder="staff@rawabizamzam.com">
                        </div>
                    </div>

                    {{-- Input Password --}}
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-widest ml-1">Password</label>
                        <div class="relative group" x-data="{ show: false }">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="bi bi-lock text-gray-400 group-focus-within:text-primary transition-colors"></i>
                            </div>
                            <input :type="show ? 'text' : 'password'" wire:model="password" required
                                class="block w-full pl-11 pr-12 py-4 bg-slate-100/50 border-transparent rounded-2xl focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none text-sm font-medium" 
                                placeholder="••••••••">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-dark">
                                <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full btn-gradient py-4 rounded-2xl font-bold shadow-lg shadow-orange-500/30 flex items-center justify-center gap-3 group">
                        <span>Masuk ke Panel</span>
                        <i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i>
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-8 text-xs text-gray-400 font-medium">
            &copy; {{ date('Y') }} Rawabi Travel. All rights reserved.
        </p>
    </div>
</div>
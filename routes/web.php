<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::public.home')->name('homepage');
Route::livewire('/paket-umroh', 'pages::public.packages')->name('packages');
Route::livewire('/tentang-kami', 'pages::public.about')->name('about');
Route::livewire('/artikel', 'pages::public.articles')->name('articles');

Route::middleware('guest')->group(function () {
    // Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    // Route::post('/login', [LoginController::class, 'login'])->name('login.perform');
    Route::livewire('/login', 'pages::auth.login')->name('login');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::livewire('/finance-center', 'pages::finance.pos')->name('finance.pos');
    
    Route::livewire('/operations', 'pages::operations.dashboard')->name('operations.dashboard');

    Route::livewire('/marketing', 'pages::marketing.sales_app')->name('marketing.salesApp');

    Route::livewire('/media', 'pages::media.creative_studio')->name('media.studio');

    Route::livewire('/executive-dashboard', 'pages::control.executive_dashboard')->name('dashboard.executive');
    
    Route::get('/print/invoice/{id}', [PrintController::class, 'printInvoice'])->name('print.invoice');
    Route::get('/print/daily-report', [PrintController::class, 'printDailyReport'])->name('print.daily_report');
});
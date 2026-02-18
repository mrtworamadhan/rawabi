<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Models\Expense;
use App\Observers\ExpenseObserver;
use App\Models\Payment;
use App\Observers\PaymentObserver;
use App\Models\Booking;
use App\Observers\BookingObserver;
use App\Models\Lead;
use App\Models\CorporateLead;
use App\Observers\LeadObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Expense::observe(ExpenseObserver::class);
        Payment::observe(PaymentObserver::class);
        Booking::observe(BookingObserver::class);
        Lead::observe(LeadObserver::class);
        CorporateLead::observe(LeadObserver::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}

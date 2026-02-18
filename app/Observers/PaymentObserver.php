<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Payment;

class PaymentObserver
{
    public function saved(Payment $payment): void
    {
        $this->updateBookingStatus($payment->booking);
    }
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->updateBookingStatus($payment->booking);
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }

    private function updateBookingStatus(Booking $booking): void
    {
        $totalPaid = $booking->payments()
            ->whereNotNull('verified_at')
            ->sum('amount');

        $totalPrice = $booking->total_price;
        
        $minimumDP = 10000000; 

        if ($booking->status === 'cancelled') {
            return;
        }

        if ($totalPaid >= $totalPrice) {
            if ($booking->status !== 'paid_in_full') {
                $booking->update(['status' => 'paid_in_full']); 
            }
        } 
        elseif ($totalPaid >= $minimumDP) {
            if ($booking->status !== 'dp_paid') {
                $booking->update(['status' => 'dp_paid']);
            }
        } 
        else {
            if ($booking->status !== 'booking') {
                $booking->update(['status' => 'booking']);
            }
        }
    }
}

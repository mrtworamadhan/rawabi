<?php

namespace App\Observers;

use App\Models\AgentTransaction;
use App\Models\AgentWallet;
use App\Models\Booking;
use App\Models\Setting;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        if ($booking->isDirty('status') && 
            $booking->status === 'paid_in_full' && 
            $booking->agent_id) {
            
            $this->processAgentCommission($booking);
        }
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }

    protected function processAgentCommission(Booking $booking)
    {
        $agent = $booking->agent;
        if (!$agent) return;

        $amount = 0;
        
        if ($agent->commission_override && $agent->commission_override > 0) {
            $amount = $agent->commission_override;
        } 
        else {
            $defaultComm = Setting::where('key', 'default_agent_commission')->value('value');
            $amount = $defaultComm ? (float) $defaultComm : 0;
        }

        if ($amount <= 0) return;

        $totalCommission = $amount * ($booking->total_pax ?? 1);

        $wallet = AgentWallet::firstOrCreate(
            ['agent_id' => $agent->id],
            ['balance' => 0]
        );

        $exists = AgentTransaction::where('agent_wallet_id', $wallet->id)
            ->where('reference_type', 'booking')
            ->where('reference_id', $booking->id)
            ->exists();

        if (!$exists) {
            $wallet->increment('balance', $totalCommission);

            AgentTransaction::create([
                'agent_wallet_id' => $wallet->id,
                'type' => 'in',
                'amount' => $totalCommission,
                'reference_type' => 'booking',
                'reference_id' => $booking->id,
                'description' => "Komisi Booking #{$booking->booking_code} ({$booking->jamaah->name}) - {$booking->umrahPackage->name}",
            ]);
            
            \Log::info("Komisi cair untuk Agen {$agent->name}: Rp {$totalCommission}");
        }
    }
}

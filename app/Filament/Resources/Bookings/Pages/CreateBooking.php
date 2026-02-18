<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Payment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    public function mount(): void
    {
        parent::mount(); 

        if (request()->has('jamaah_id')) {
            $this->form->fill([
                'jamaah_id' => request()->query('jamaah_id'),
                'booking_code' => 'RZ-' . strtoupper(uniqid()), 
                'created_at' => now()->format('Y-m-d'),
                'status' => 'booking',
                'sales_id' => request()->query('sales_id'),
                'agent_id' => request()->query('agent_id'),
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            
            $dpAmount = $data['dp_amount'];
            $dpMethod = $data['dp_method'];
            $dpProof  = $data['dp_proof'];

            unset($data['dp_amount']);
            unset($data['dp_method']);
            unset($data['dp_proof']);

            $booking = static::getModel()::create($data);

            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $dpAmount,
                'type' => 'dp',
                'method' => $dpMethod,
                'proof_file' => $dpProof,
                'created_at' => now(),
                
                'verified_at' => null, 
                'verified_by' => null,
            ]);

            return $booking;
        });
    }
}

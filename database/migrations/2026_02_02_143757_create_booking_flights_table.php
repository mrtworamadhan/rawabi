<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_flight_id')->constrained()->cascadeOnDelete(); // Link ke jadwal flight spesifik
            
            $table->string('pnr_code')->nullable();
            $table->string('ticket_number')->nullable();
            $table->string('notes')->nullable(); 
            
            $table->timestamps();
            
            $table->unique(['booking_id', 'package_flight_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_flights');
    }
};

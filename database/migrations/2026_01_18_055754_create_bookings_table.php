<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique(); 
            $table->foreignId('jamaah_id')->constrained('jamaahs')->cascadeOnDelete();
            $table->foreignId('umrah_package_id')->constrained()->cascadeOnDelete();
            
            $table->foreignId('sales_id')->nullable()->constrained('employees')->nullOnDelete();
            
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            
            $table->decimal('total_price', 15, 2);
            $table->enum('status', ['booking', 'dp_paid', 'paid_in_full', 'reschedule', 'cancelled'])->default('booking');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

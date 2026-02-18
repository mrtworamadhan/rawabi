<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umrah_package_id')->constrained()->cascadeOnDelete();
            $table->string('hotel_name')->nullable();
            $table->string('room_number')->nullable(); 
            $table->enum('room_type', ['quad', 'triple', 'double', 'single'])->default('quad');
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
    }
};

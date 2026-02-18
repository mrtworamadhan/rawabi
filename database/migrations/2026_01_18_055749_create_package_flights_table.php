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
        Schema::create('package_flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umrah_package_id')->constrained()->cascadeOnDelete();
            $table->string('airline');
            $table->string('flight_number');
            $table->string('depart_airport');
            $table->string('arrival_airport');
            $table->dateTime('depart_at');
            $table->dateTime('arrive_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_flights');
    }
};

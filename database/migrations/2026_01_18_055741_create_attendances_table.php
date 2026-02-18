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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date'); 
            $table->time('clock_in_time');
            $table->time('clock_out_time')->nullable();
            $table->string('clock_in_location')->nullable();
            $table->string('clock_in_photo')->nullable();
            $table->enum('status', ['on_time', 'late', 'alpha', 'permit'])->default('on_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

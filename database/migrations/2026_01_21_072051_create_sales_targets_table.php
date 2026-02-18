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
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();            
            
            $table->date('start_date'); 
            $table->date('end_date');
            
            $table->integer('target_jamaah');
            $table->decimal('target_omset', 15, 2)->nullable();
            
            $table->foreignId('set_by')->constrained('users');
            
            $table->timestamps();
            
            $table->unique(['employee_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};

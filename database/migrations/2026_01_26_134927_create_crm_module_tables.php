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
        // 1. Leads Perorangan (B2C)
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('city')->nullable();
            $table->string('source')->nullable(); // Facebook, Instagram, Walk-in
            
            // Relasi (Nullable karena bisa jadi direct tanpa agen)
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('sales_id')->nullable()->constrained('employees')->nullOnDelete();
            
            $table->string('potential_package')->nullable(); // Minat paket apa
            $table->enum('status', ['cold', 'warm', 'hot', 'closing', 'lost', 'converted'])->default('cold');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Leads Perusahaan (B2B)
        Schema::create('corporate_leads', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('pic_name');
            $table->string('pic_phone')->unique();
            $table->string('address')->nullable();
            $table->integer('potential_pax')->default(0);
            $table->decimal('budget_estimation', 15, 2)->nullable();
            
            $table->foreignId('sales_id')->nullable()->constrained('employees')->nullOnDelete();
            
            $table->enum('status', ['prospecting', 'presentation', 'negotiation', 'deal', 'lost'])->default('prospecting');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('corporate_leads');
    }
};

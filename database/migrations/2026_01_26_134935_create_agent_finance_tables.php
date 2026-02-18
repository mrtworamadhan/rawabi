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
        // 1. Dompet Agen
        Schema::create('agent_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });

        // 2. Transaksi Dompet
        Schema::create('agent_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->decimal('amount', 15, 2);
            
            $table->string('reference_type')->nullable(); // 'booking', 'withdrawal'
            $table->unsignedBigInteger('reference_id')->nullable(); 
            
            $table->string('description')->nullable();
            $table->string('proof_file')->nullable();
            $table->timestamps();
        });

        // 3. Settings Global
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary(); 
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_wallets');
        Schema::dropIfExists('agent_transactions');
        Schema::dropIfExists('settings');
    }
};

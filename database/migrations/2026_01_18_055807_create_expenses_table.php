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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            
            $table->date('transaction_date');
            $table->string('name');
            $table->decimal('amount', 15, 2);
            
            $table->string('proof_file')->nullable();
            
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            
            $table->text('note')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

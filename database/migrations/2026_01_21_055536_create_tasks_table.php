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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('employees')->nullOnDelete(); 

            $table->foreignId('task_template_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->dateTime('due_date');
            $table->integer('priority')->default(1);

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->string('proof_file')->nullable();
            $table->text('completion_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

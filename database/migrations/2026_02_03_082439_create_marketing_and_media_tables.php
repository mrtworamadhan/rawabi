<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('leads', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('phone');
        //     $table->string('source')->nullable();
        //     $table->enum('status', ['new', 'contacted', 'warm', 'hot', 'closing', 'lost'])->default('new');
        //     $table->text('notes')->nullable();
            
        //     $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            
        //     $table->timestamps();
        // });

        // Schema::create('sales_tasks', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('lead_id')->nullable()->constrained('leads')->cascadeOnDelete();
        //     $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        //     $table->string('title');
        //     $table->dateTime('due_date');
        //     $table->boolean('is_completed')->default(false);
        //     $table->timestamps();
        // });

        Schema::create('content_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title'); 
            $table->date('scheduled_date');
            $table->json('platforms')->nullable();
            $table->enum('status', ['idea', 'drafting', 'ready', 'published'])->default('idea');
            $table->text('caption_draft')->nullable();
            $table->string('attachment_path')->nullable(); 
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umrah_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path');
            $table->string('file_type')->default('image');
            $table->string('title')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('content_schedules');
        Schema::dropIfExists('sales_tasks');
        Schema::dropIfExists('leads');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('rundowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umrah_package_id')->constrained()->cascadeOnDelete();
            $table->string('phase')->default('during');
            $table->date('date')->nullable(); 
            $table->integer('day_number')->default(1);
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('activity');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('pic_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rundowns');
    }
};

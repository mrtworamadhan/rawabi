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
        Schema::create('document_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->boolean('ktp')->default(false);
            $table->boolean('kk')->default(false);
            $table->boolean('buku_nikah')->default(false);
            $table->enum('passport_status', ['missing', 'on_process', 'received'])->default('missing');
            $table->enum('visa_status', ['pending', 'requested', 'issued'])->default('pending');
            $table->string('visa_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_checks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('airline_pnr')->nullable()->after('status');
            $table->string('airline_ticket_number')->nullable()->after('airline_pnr');
            $table->text('special_notes')->nullable()->after('airline_ticket_number'); // Kursi roda, dll
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['airline_pnr', 'airline_ticket_number', 'special_notes']);
        });
    }
};

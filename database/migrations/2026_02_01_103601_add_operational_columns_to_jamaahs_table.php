<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jamaahs', function (Blueprint $table) {
            $table->string('passport_scan')->nullable()->after('passport_expiry');
            $table->string('vaccine_status')->nullable()->after('passport_scan');
            $table->string('vaccine_certificate')->nullable()->after('vaccine_status');
        });
    }

    public function down(): void
    {
        Schema::table('jamaahs', function (Blueprint $table) {
            $table->dropColumn(['passport_scan', 'vaccine_status', 'vaccine_certificate']);
        });
    }
};

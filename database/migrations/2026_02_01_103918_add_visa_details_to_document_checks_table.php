<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_checks', function (Blueprint $table) {
            $table->string('visa_number')->nullable()->after('visa_status');
            $table->date('visa_issue_date')->nullable()->after('visa_number');
            $table->date('visa_expiry_date')->nullable()->after('visa_issue_date');
        });
    }

    public function down(): void
    {
        Schema::table('document_checks', function (Blueprint $table) {
            $table->dropColumn(['visa_number', 'visa_issue_date', 'visa_expiry_date']);
        });
    }
};

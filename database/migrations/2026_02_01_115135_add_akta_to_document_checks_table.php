<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_checks', function (Blueprint $table) {
            $table->boolean('akta')->default(false)->after('kk');
        });
    }

    public function down(): void
    {
        Schema::table('document_checks', function (Blueprint $table) {
            $table->dropColumn('akta');
        });
    }
};

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
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('office_wallet_id')->nullable()->change(); 

            $table->foreignId('bank_account_id')->nullable()->after('office_wallet_id')->constrained('bank_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $dropForeign = 'expenses_office_wallet_id_foreign';
            $table->dropForeign($dropForeign);
            $table->dropColumn('bank_account_id');
        });
    }
};

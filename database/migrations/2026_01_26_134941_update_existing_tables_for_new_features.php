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
        // Update Employees: Tambah Department ID
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('address_domicile')->constrained('departments')->nullOnDelete();
        });

        // Update Agents: Tambah Sales Pembina & Override Komisi
        Schema::table('agents', function (Blueprint $table) {
            $table->foreignId('sales_id')->nullable()->after('phone')->constrained('employees')->nullOnDelete();
            $table->decimal('commission_override', 15, 2)->nullable()->after('sales_id')->comment('Isi jika beda dari default');
        });
        
        // Update Sales Target: Tambah info bonus
        Schema::table('sales_targets', function (Blueprint $table) {
            $table->decimal('bonus_amount', 15, 2)->default(0)->after('target_omset');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('id')->constrained('departments')->cascadeOnDelete();
            $table->time('deadline_time')->nullable()->after('is_active'); 
            $table->boolean('is_mandatory')->default(true)->after('deadline_time');
            
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('proof_link')->nullable()->after('proof_file'); // Untuk link IG/Drive
            $table->integer('score')->nullable()->after('status'); // Nilai 0-100
            $table->text('admin_note')->nullable()->after('completion_note'); // Feedback Bos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['sales_id']);
            $table->dropColumn('sales_id');
            $table->dropColumn('commission_override');
        });
        Schema::table('sales_targets', function (Blueprint $table) {
            $table->dropColumn('bonus_amount');
        });
        
        Schema::table('task_templates', fn (Blueprint $table) => 
            $table->dropColumn(['department_id', 'deadline_time', 'is_mandatory']));
        
        Schema::table('tasks', fn (Blueprint $table) => 
            $table->dropColumn(['proof_link', 'score', 'admin_note']));
    }
};

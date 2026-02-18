<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaskTemplate;
use App\Models\Department;
use Illuminate\Support\Facades\Schema;

class TaskTemplateSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        TaskTemplate::truncate();
        Schema::enableForeignKeyConstraints();

        $mkt = Department::where('code', 'MKT')->first()?->id;
        $fin = Department::where('code', 'FIN')->first()?->id;
        $med = Department::where('code', 'MED')->first()?->id;
        $ops = Department::where('code', 'OPS')->first()?->id;

        if (!$mkt || !$fin || !$med || !$ops) {
            $this->command->error('Departemen belum lengkap! Jalankan DepartmentMigrationSeeder dulu.');
            return;
        }

        $templates = [
            // --- MARKETING (MKT) ---
            [
                'department_id' => $mkt,
                'title' => 'Follow Up 10 Leads Cold/Warm',
                'description' => 'Hubungi minimal 10 calon jamaah dari database CRM. Update status mereka (misal: jadi Hot/Closing). Wajib upload screenshot chat/log panggilan.',
                'frequency' => 'daily',
                'deadline_time' => '17:00:00',
                'is_mandatory' => true,
            ],
            [
                'department_id' => $mkt,
                'title' => 'Update Status Prospek Corporate',
                'description' => 'Cek status proposal ke instansi. Jika ada perkembangan, update di menu Corporate Leads.',
                'frequency' => 'weekly', 
                'deadline_time' => '12:00:00',
                'is_mandatory' => true,
            ],
            [
                'department_id' => $mkt,
                'title' => 'Rekap Pencapaian Target Bulanan',
                'description' => 'Buat laporan evaluasi target sales bulan ini. Apa kendalanya dan rencana bulan depan.',
                'frequency' => 'monthly', 
                'deadline_time' => '17:00:00',
                'is_mandatory' => true,
            ],

            // --- FINANCE (FIN) ---
            [
                'department_id' => $fin,
                'title' => 'Cek Mutasi & Verifikasi Pembayaran',
                'description' => 'Cek mutasi bank hari ini. Verifikasi pembayaran jamaah yang masuk di sistem. Pastikan verified_at terisi.',
                'frequency' => 'daily',
                'deadline_time' => '11:00:00', 
                'is_mandatory' => true,
            ],
            [
                'department_id' => $fin,
                'title' => 'Follow Up Tagihan Jatuh Tempo',
                'description' => 'Cek widget "Billing Monitor". WA jamaah yang sudah jatuh tempo pembayaran pelunasan.',
                'frequency' => 'daily',
                'deadline_time' => '15:00:00',
                'is_mandatory' => true,
            ],
            [
                'department_id' => $fin,
                'title' => 'Rekap Kas Kecil (Petty Cash)',
                'description' => 'Hitung fisik uang kas kecil dan cocokan dengan saldo di sistem.',
                'frequency' => 'weekly',
                'deadline_time' => '16:00:00',
                'is_mandatory' => true,
            ],
            [
                'department_id' => $fin,
                'title' => 'Laporan Laba Rugi Bulanan',
                'description' => 'Finalisasi laporan keuangan bulan lalu untuk meeting owner.',
                'frequency' => 'monthly',
                'deadline_time' => '17:00:00',
                'is_mandatory' => true,
            ],

            // --- MEDIA & CREATIVE (MED) ---
            [
                'department_id' => $med,
                'title' => 'Posting 1 Konten Instagram/Tiktok',
                'description' => 'Upload konten harian sesuai content calendar. Wajib input Link Postingan di bukti tugas.',
                'frequency' => 'daily',
                'deadline_time' => '19:00:00', 
                'is_mandatory' => true,
            ],
            [
                'department_id' => $med,
                'title' => 'Balas Komentar & DM Sosmed',
                'description' => 'Pastikan interaksi followers terjaga. Reply komen dan DM yang masuk.',
                'frequency' => 'daily',
                'deadline_time' => '10:00:00',
                'is_mandatory' => true,
            ],
            [
                'department_id' => $med,
                'title' => 'Content Planning Mingguan',
                'description' => 'Meeting tim kreatif untuk jadwal konten minggu depan.',
                'frequency' => 'weekly',
                'deadline_time' => '14:00:00',
                'is_mandatory' => true,
            ],

            // --- OPERATIONAL / INVENTORY (OPS) ---
            [
                'department_id' => $ops,
                'title' => 'Cek Kebersihan & Kerapihan Gudang',
                'description' => 'Pastikan barang perlengkapan tersusun rapi. Foto kondisi gudang.',
                'frequency' => 'daily',
                'deadline_time' => '09:00:00',
                'is_mandatory' => true,
            ],
            [
                'department_id' => $ops,
                'title' => 'Stock Opname Perlengkapan (Koper/Kain)',
                'description' => 'Hitung fisik stok koper, kain ihram, dan mukena. Update di sistem jika ada selisih.',
                'frequency' => 'weekly',
                'deadline_time' => '16:00:00',
                'is_mandatory' => true,
            ],
            [
                'department_id' => $ops,
                'title' => 'Maintenance Aset Kantor',
                'description' => 'Cek kondisi AC, Printer, Mobil Operasional. Jadwalkan service jika perlu.',
                'frequency' => 'monthly',
                'deadline_time' => '17:00:00',
                'is_mandatory' => false,
            ],
        ];

        foreach ($templates as $data) {
            TaskTemplate::create($data);
        }
    }
}
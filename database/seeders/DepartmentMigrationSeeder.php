<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Employee;

class DepartmentMigrationSeeder extends Seeder
{
    public function run(): void
    {
        $depts = [
            ['name' => 'Board Of Directors', 'code' => 'BOD'],
            ['name' => 'Marketing & Sales', 'code' => 'MKT'],
            ['name' => 'Finance & Admin',   'code' => 'FIN'],
            ['name' => 'Operational & Inventory', 'code' => 'OPS'],
            ['name' => 'Media & Creative',  'code' => 'MED'],
            ['name' => 'Human Resources',   'code' => 'HRD'],
            ['name' => 'Agent Relations',   'code' => 'AGT'],
        ];

        foreach ($depts as $d) {
            Department::firstOrCreate(
                ['code' => $d['code']], 
                ['name' => $d['name']]
            );
        }

        // 2. Migrasi Data Karyawan Lama
        // Kita cari karyawan yang department_id nya masih kosong
        $employees = Employee::whereNull('department_id')->get();

        foreach ($employees as $emp) {
            $oldDept = strtolower($emp->department); // Ambil string lama
            $newDeptCode = null;

            // Logika pencocokan manual (Sesuaikan dengan data lama di databasemu)
            if (str_contains($oldDept, 'marketing') || str_contains($oldDept, 'sales')) {
                $newDeptCode = 'MKT';
            } elseif (str_contains($oldDept, 'finance') || str_contains($oldDept, 'admin') || str_contains($oldDept, 'keuangan')) {
                $newDeptCode = 'FIN';
            } elseif (str_contains($oldDept, 'operasional') || str_contains($oldDept, 'gudang') || str_contains($oldDept, 'perlengkapan')) {
                $newDeptCode = 'OPS';
            } elseif (str_contains($oldDept, 'it') || str_contains($oldDept, 'design')) {
                $newDeptCode = 'MED';
            } elseif (str_contains($oldDept, 'hr & ga') || str_contains($oldDept, 'hr')) {
                $newDeptCode = 'HRD';
            } elseif (str_contains($oldDept, 'board of directors')) {
                $newDeptCode = 'BOD';
            }
            

            // Update ID
            if ($newDeptCode) {
                $dept = Department::where('code', $newDeptCode)->first();
                if ($dept) {
                    $emp->update(['department_id' => $dept->id]);
                }
            }
        }
    }
}
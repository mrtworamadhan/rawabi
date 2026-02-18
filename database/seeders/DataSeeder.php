<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\InventoryItem;
use App\Models\Jamaah;
use App\Models\TaskTemplate;
use App\Models\UmrahPackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DataSeeder extends Seeder
{
    public function run(): void
    {
        // 0. SETUP ROLES
        $roles = ['super_admin', 'owner', 'finance', 'marketing', 'operasional', 'human_resource', 'media', 'jamaah'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
        $this->command->info('✅ Roles Created');

        // 1. SEEDER KARYAWAN (EMPLOYEES)
        $employeesData = [
            [
                'name' => 'Owner',
                'email' => 'owner@rawabi.com',
                'pos' => 'Direktur Utama',
                'dept' => 'Board of Directors',
                'role' => 'super_admin'
            ],
            [
                'name' => 'Rani Keuangan',
                'email' => 'finance@rawabi.com',
                'pos' => 'Finance Manager',
                'dept' => 'Finance',
                'role' => 'finance'
            ],
            [
                'name' => 'Zamzami Sales A',
                'email' => 'sales1@rawabi.com',
                'pos' => 'Sales Executive',
                'dept' => 'Marketing',
                'role' => 'marketing'
            ],
            [
                'name' => 'Sukma Sales B',
                'email' => 'sales2@rawabi.com',
                'pos' => 'Sales Senior',
                'dept' => 'Marketing',
                'role' => 'marketing'
            ],
            [
                'name' => 'Tri Operasional',
                'email' => 'ops@rawabi.com',
                'pos' => 'Ops Manager',
                'dept' => 'Operasional',
                'role' => 'operasional'
            ],
            [
                'name' => 'Hera HRD',
                'email' => 'hrd@rawabi.com',
                'pos' => 'HR Manager',
                'dept' => 'HR & GA',
                'role' => 'human_resource'
            ],
            [
                'name' => 'IT Support',
                'email' => 'it@rawabi.com',
                'pos' => 'IT Staff',
                'dept' => 'IT',
                'role' => 'media'
            ],
        ];

        $marketingIds = [];

        foreach ($employeesData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    // 'is_active' => true,
                ]
            );
            
            $user->assignRole($data['role']);

            $emp = Employee::firstOrCreate([
                'user_id' => $user->id,
                'nik_karyawan' => 'RW-' . rand(1000, 9999),
                'full_name' => $data['name'],
                'nickname' => explode(' ', $data['name'])[0],
                'place_of_birth' => 'Jakarta',
                'date_of_birth' => '1990-01-01',
                'gender' => 'pria',
                'phone_number' => '0812' . rand(10000000, 99999999),
                'address_ktp' => 'Jl. Data Seeder No. 1',
                'address_domicile' => 'Jl. Data Seeder No. 1',
                'department' => $data['dept'],
                'position' => $data['pos'],
                'join_date' => now()->subYears(1),
                'status' => 'permanent',
            ]);

            if ($data['role'] === 'marketing') {
                $marketingIds[] = $emp->id;
            }
        }
        $this->command->info('✅ Employees Created');

        // 2. SEEDER EXPENSE CATEGORY
        $categories = [
            ['name' => 'Tiket Pesawat & Visa', 'type' => 'hpp'],
            ['name' => 'Hotel & Akomodasi', 'type' => 'hpp'],
            ['name' => 'Perlengkapan Jamaah', 'type' => 'hpp'],
            ['name' => 'Transportasi Lokal', 'type' => 'hpp'],
            ['name' => 'Gaji Karyawan', 'type' => 'operational'],
            ['name' => 'Operasional Kantor (Listrik/Air)', 'type' => 'operational'],
            ['name' => 'Marketing & Iklan', 'type' => 'operational'],
            ['name' => 'Komisi Agen', 'type' => 'operational'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate($cat);
        }
        $this->command->info('✅ Expense Categories Created');

        // 3. SEEDER INVENTORY ITEMS
        $items = [
            ['name' => 'Koper', 'stock_quantity' => '100', 'type' => 'umum'],
            ['name' => 'Tas Kabin', 'stock_quantity' => '100', 'type' => 'umum'],
            ['name' => 'Tas Passport', 'stock_quantity' => '100', 'type' => 'umum'],
            ['name' => 'Mukena', 'stock_quantity' => '100', 'type' => 'wanita'],
            ['name' => 'Kain Ihrom', 'stock_quantity' => '100', 'type' => 'pria'],
            ['name' => 'Syal', 'stock_quantity' => '100', 'type' => 'umum'],
            ['name' => 'Batik Pria', 'stock_quantity' => '100', 'type' => 'pria'],
            ['name' => 'Batik Wanita', 'stock_quantity' => '100', 'type' => 'wanita'],
            ['name' => 'Buku Panduan', 'stock_quantity' => '100', 'type' => 'umum'],
        ];

        foreach ($items as $item) {
            InventoryItem::firstOrCreate($item);
        }
        $this->command->info('✅ Inventory Items Created');

        // 4. SEEDER AGENT
        for ($i = 1; $i <= 10; $i++) {
            Agent::firstOrCreate([
                'name' => 'Agen Travel ' . $i,
                'phone' => '0899' . rand(1000000, 9999999) . $i,
                'address' => 'Jl. Agen No. ' . $i,
                'commission_amount' => 1500000.00,
            ]);
        }
        $this->command->info('✅ Agents Created');

        // 5. SEEDER TASK TEMPLATE
        $tasks = [
            ['dept' => 'Finance', 'title' => 'Laporan Kas Harian', 'freq' => 'daily'],
            ['dept' => 'Marketing', 'title' => 'Follow Up Leads', 'freq' => 'daily'],
            ['dept' => 'Operasional', 'title' => 'Cek Stok Gudang', 'freq' => 'monthly'],
            ['dept' => 'HR & GA', 'title' => 'Rekap Absensi', 'freq' => 'monthly'],
            ['dept' => 'IT', 'title' => 'Backup Database', 'freq' => 'daily'],
            ['dept' => 'All', 'title' => 'Meeting Mingguan', 'freq' => 'adhoc'], 
        ];

        foreach ($tasks as $t) {
            TaskTemplate::firstOrCreate([
                'title' => $t['title'],
                'description' => 'SOP Rutin',
                'frequency' => $t['freq'],
                'target_department' => $t['dept'],
                'is_active' => true
            ]);
        }
        $this->command->info('✅ Task Templates Created');

        // 6. SEEDER UMRAH PACKAGES
        $packages = [
            [
                'name' => 'Umroh Januari',
                'date' => '2025-01-20',
                'price' => 32000000,
            ],
            [
                'name' => 'Umroh Februari',
                'date' => '2025-02-15',
                'price' => 29500000,
            ],
            [
                'name' => 'Umroh Maret',
                'date' => '2025-03-10',
                'price' => 38000000,
            ],
        ];

        $createdPackages = [];
        foreach ($packages as $pkg) {
            $createdPackages[] = UmrahPackage::firstOrCreate([
                'name' => $pkg['name'],
                'price' => $pkg['price'],
                'target_jamaah' => 40,
                'departure_date' => $pkg['date'],
                'return_date' => \Carbon\Carbon::parse($pkg['date'])->addDays(9),
                'status' => 'open',
            ]);
        }
        $this->command->info('✅ Umrah Packages Created');

        // 7. SEEDER BOOKINGS
        $agents = Agent::all();
        
        $scenarios = [
            ['pkg_idx' => 0, 'count' => 30],
            ['pkg_idx' => 1, 'count' => 25],
            ['pkg_idx' => 2, 'count' => 10], 
        ];

        foreach ($scenarios as $scenario) {
            $package = $createdPackages[$scenario['pkg_idx']];
            
            for ($i = 0; $i < $scenario['count']; $i++) {
            $gender = rand(0, 1) ? 'pria' : 'wanita';
            $name = fake()->name($gender == 'pria' ? 'male' : 'female');

            $user = User::create([
                'name' => $name,
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                // 'is_active' => true,
            ]);
            $user->assignRole('jamaah');

            $jamaah = Jamaah::create([
                'user_id' => $user->id,
                'name' => $name,
                'nik' => fake()->numerify('################'),                 
                'gender' => $gender,
                'phone' => fake()->phoneNumber(),
            ]);

            $pakeAgent = rand(1, 100) <= 70;
            $agentId = $pakeAgent ? $agents->random()->id : null;
            $salesId = !empty($marketingIds) ? $marketingIds[array_rand($marketingIds)] : null;

            Booking::create([
                'booking_code' => 'BK-' . strtoupper(fake()->bothify('??####')),
                'jamaah_id' => $jamaah->id,
                'umrah_package_id' => $package->id,
                'sales_id' => $salesId,
                'agent_id' => $agentId,
                'total_price' => $package->price,
                'status' => 'booking',
                'notes' => 'Seeder Data',
            ]);
        }
        }
        $this->command->info('✅ Bookings Created');
        $this->command->info('🎉 SEEDING COMPLETE!');
    }
}
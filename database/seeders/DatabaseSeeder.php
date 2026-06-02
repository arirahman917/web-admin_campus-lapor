<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Super Admin 1',
                'username' => 'superadmin1',
                'email' => 'superadmin1@kampus-lapor.test',
                'password' => Hash::make('superadmin123'),
                'role' => 'superadmin',
                'status' => 'aktif',
                'identifier' => 'SA-0001',
                'unit' => 'Pusat Sistem Informasi Kampus',
                'phone' => '081200000001',
            ],
            [
                'name' => 'Admin Kampus 1',
                'username' => 'admin1',
                'email' => 'admin1@kampus-lapor.test',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'aktif',
                'identifier' => 'AD-0001',
                'unit' => 'Direktorat Sarana dan Prasarana',
                'phone' => '081200000002',
            ],
            [
                'name' => 'Civitas Mobile 1',
                'username' => 'civitas1',
                'email' => 'civitas1@kampus-lapor.test',
                'password' => Hash::make('civitas123'),
                'role' => 'civitas',
                'status' => 'aktif',
                'identifier' => 'CV-0001',
                'unit' => 'Fakultas Ilmu Komputer',
                'phone' => '081200000003',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['username' => $account['username']],
                $account
            );
        }
    }
}

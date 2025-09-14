<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
        ]);

        // User Sysadmin (Kepala Sekolah)
        User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'admin@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'sysadmin',
        ]);

        // User Admin (Staf TU)
        User::create([
            'name' => 'Staf TU',
            'email' => 'staf@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}

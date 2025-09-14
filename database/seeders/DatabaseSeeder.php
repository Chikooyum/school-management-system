<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,       // 1. Buat user dulu
            ClassGroupSeeder::class,
            CostItemSeeder::class,// 2. Buat kelas
            StudentSeeder::class,    // 3. Baru buat siswa
        ]);
    }
}

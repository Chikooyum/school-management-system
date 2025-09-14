<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClassGroup;
use App\Models\Staff;

class ClassGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat beberapa contoh guru
        $teacher1 = Staff::firstOrCreate(
            ['name' => 'Tc. Ani Suryani'],
            ['position' => 'Guru TK A', 'contact_info' => '081234567890']
        );
        $teacher2 = Staff::firstOrCreate(
            ['name' => 'Tc. Budi Setiawan'],
            ['position' => 'Guru TK B', 'contact_info' => '081234567891']
        );

        // Buat beberapa contoh kelas dan langsung tetapkan wali kelasnya
        ClassGroup::firstOrCreate(
            ['name' => 'TK A Ceria'],
            ['enrollment_year' => 2025, 'staff_id' => $teacher1->id]
        );
        ClassGroup::firstOrCreate(
            ['name' => 'TK A Bintang'],
            ['enrollment_year' => 2025]
        );
        ClassGroup::firstOrCreate(
            ['name' => 'TK B Pelangi'],
            ['enrollment_year' => 2024, 'staff_id' => $teacher2->id]
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassGroup;
use App\Models\Staff;
use App\Models\User;

class ClassGroupSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cari akun login guru 'Ani Suryani' yang sudah dibuat di UserSeeder
        $teacherUser1 = User::where('email', 'guru.ani@sekolah.guru')->first();

        // Variabel untuk menampung data staf setelah dibuat
        $teacher1 = null;

        // 2. Jika akun loginnya ketemu...
        if ($teacherUser1) {
            // ...buat profil stafnya DAN langsung hubungkan dengan user_id
            $teacher1 = Staff::firstOrCreate(
                ['name' => 'Tc. Ani Suryani'],
                [
                    'position' => 'Guru TK A',
                    'contact_info' => '081234567890',
                    'user_id' => $teacherUser1->id // <-- Ini bagian kuncinya
                ]
            );
        }

        // Contoh guru kedua (Budi Setiawan) yang tidak memiliki akun login
        $teacher2 = Staff::firstOrCreate(
            ['name' => 'Tc. Budi Setiawan'],
            ['position' => 'Guru TK B', 'contact_info' => '081234567891']
        );

        // 3. Buat kelas-kelasnya
        // Jika staf Ani Suryani berhasil dibuat, tetapkan dia sebagai wali kelas
        if ($teacher1) {
            ClassGroup::firstOrCreate(
                ['name' => 'TK A Ceria'],
                ['enrollment_year' => 2025, 'staff_id' => $teacher1->id]
            );
        }

        ClassGroup::firstOrCreate(
            ['name' => 'TK B Bintang'],
            ['enrollment_year' => 2025, 'staff_id' => $teacher2->id]
        );
    }
}

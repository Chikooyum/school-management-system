<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\ClassGroup;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $classes = ClassGroup::all();
        if ($classes->isEmpty()) {
            $this->command->error("Tidak ada kelas yang tersedia. Silakan buat kelas terlebih dahulu.");
            return;
        }

        Student::factory(50)->create()->each(function ($student) use ($classes) {
            // Tetapkan siswa ke kelas acak
            $student->class_group_id = $classes->random()->id;
            $student->save();

            // Panggil method penagihan otomatis
            $student->createInitialBills();
        });
    }
}

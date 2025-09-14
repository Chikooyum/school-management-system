<?php

namespace App\Console\Commands;

use App\Models\CostItem;
use App\Models\Student;
use App\Models\StudentBill;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BillMonthlySpp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // PERUBAHAN 1: Tambahkan argumen opsional --month dan --year
    protected $signature = 'spp:bill {--month=} {--year=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat tagihan SPP bulanan untuk semua siswa aktif';

    /**
     * Execute the console command.
     */
    public function handle()
{
    $this->info('Memulai proses penagihan SPP bulanan...');

    // Logika baru: Cari item SPP yang sudah ada
    $monthName = Carbon::now()->translatedFormat('F Y');
    $sppThisMonthName = "SPP {$monthName}";

    $sppThisMonth = CostItem::where('name', $sppThisMonthName)->first();

    if (!$sppThisMonth) {
        $this->error("Gagal! Item biaya '{$sppThisMonthName}' tidak ditemukan. Pastikan seeder sudah dijalankan.");
        return 1;
    }
    $this->info("Item biaya '{$sppThisMonthName}' ditemukan.");

    // Sisa logika untuk menagih ke siswa tidak berubah...
    $activeStudents = Student::where('status', 'Aktif')->get();
    if ($activeStudents->isEmpty()) {
        $this->warn('Tidak ada siswa aktif. Proses selesai.');
        return 0;
    }

    $billedCount = 0;
    foreach ($activeStudents as $student) {
        $existingBill = StudentBill::where('student_id', $student->id)
                                    ->where('cost_item_id', $sppThisMonth->id)
                                    ->exists();
        if (!$existingBill) {
            StudentBill::create([
                'student_id' => $student->id,
                'cost_item_id' => $sppThisMonth->id,
                'remaining_amount' => $sppThisMonth->amount,
                'status' => 'Belum Lunas',
            ]);
            $billedCount++;
        }
    }

    $this->info("Penagihan selesai. {$billedCount} tagihan SPP baru telah dibuat.");
    return 0;
}
}

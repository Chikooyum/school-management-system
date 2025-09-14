<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CostItem;
use Carbon\Carbon;


class CostItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Tagihan Otomatis
            ['name' => 'Uang Foto Tahunan', 'type' => 'Tetap', 'amount' => 25000, 'cost_code' => 'FOTO'],
            ['name' => 'Uang Rapot', 'type' => 'Tetap', 'amount' => 70000, 'cost_code' => 'RAPOT'],
            ['name' => 'Uang Ijazah', 'type' => 'Tetap', 'amount' => 120000, 'cost_code' => 'IJAZAH'],

            // Uang Gedung
            ['name' => 'Uang Gedung Gelombang 1', 'type' => 'Tetap', 'amount' => 700000, 'cost_code' => 'GEDUNG_G1'],
            ['name' => 'Uang Gedung Gelombang 2', 'type' => 'Tetap', 'amount' => 1000000, 'cost_code' => 'GEDUNG_G2'],
            ['name' => 'Uang Gedung Gelombang 3', 'type' => 'Tetap', 'amount' => 1200000, 'cost_code' => 'GEDUNG_G3'],

            // Uang Gedung (Diskon)
            ['name' => 'Uang Gedung Gel. 1 (Diskon Alumni)', 'type' => 'Tetap', 'amount' => 600000, 'cost_code' => 'GEDUNG_G1_ALUMNI'],

            // Template SPP
            ['name' => 'Template SPP Bulanan', 'type' => 'Tetap', 'amount' => 350000, 'cost_code' => 'SPP_TEMPLATE'],
        ];

        foreach ($items as $item) {
            CostItem::create($item);
        }
        $year = now()->year;
        $sppAmount = 135000; // Tentukan nominal SPP di sini

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create()->month($month)->translatedFormat('F');
            CostItem::create([
                'name' => "SPP {$monthName} {$year}",
                'type' => 'Tetap',
                'amount' => $sppAmount,
                // Kita tidak perlu cost_code untuk SPP bulanan
            ]);
        }

    }
}

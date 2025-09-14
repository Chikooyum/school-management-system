<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

class Student extends Model
{
    use HasFactory, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nis',
        'enrollment_year',
        'registration_wave',
        'date_of_birth',
        'father_name',
        'mother_name',
        'mother_date_of_birth',
        'address',
        'phone_number',
        'status',
        'is_alumni_sibling',
        'class_group_id',
        'user_id',
    ];

    /**
     * [TAMBAHKAN INI]
     * Menyertakan accessor unpaid_bills_count ke dalam representasi JSON/array model.
     */
        protected $appends = ['unpaid_bills_count', 'savings_balance'];


    /**
     * Relasi: Satu siswa bisa memiliki banyak tagihan.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(StudentBill::class);
    }

    /**
     * Relasi: Satu siswa bisa memiliki banyak riwayat tabungan.
     */
    public function savings(): HasMany
    {
        return $this->hasMany(StudentSaving::class);
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /**
     * [TAMBAHKAN INI]
     * Accessor untuk menghitung jumlah tagihan yang belum lunas.
     */
    public function getUnpaidBillsCountAttribute()
    {
        // Fungsi ini menggunakan relasi 'bills()' yang sudah Anda miliki
        // untuk menghitung jumlah tagihan yang statusnya bukan 'Lunas'.
        return $this->bills()->where('status', '!=', 'Lunas')->count();
    }

    public function createInitialBills()
    {
        try {
            $fixedCostCodes = ['FOTO', 'RAPOT', 'IJAZAH'];
            $fixedCostItems = CostItem::whereIn('cost_code', $fixedCostCodes)->get();

            foreach ($fixedCostItems as $item) {
                StudentBill::firstOrCreate(
                    ['student_id' => $this->id, 'cost_item_id' => $item->id],
                    ['remaining_amount' => $item->amount, 'status' => 'Belum Lunas']
                );
            }

            $wave = $this->registration_wave;
            $gedungCode = "GEDUNG_G{$wave}";
            if ($this->is_alumni_sibling) {
                $gedungCode .= "_ALUMNI";
            }

            $uangGedung = CostItem::where('cost_code', $gedungCode)->first();
            if ($uangGedung) {
                StudentBill::firstOrCreate(
                    ['student_id' => $this->id, 'cost_item_id' => $uangGedung->id],
                    ['remaining_amount' => $uangGedung->amount, 'status' => 'Belum Lunas']
                );
            }
        } catch (\Exception $e) {
            Log::error("Penagihan otomatis gagal untuk siswa ID: {$this->id}. Error: " . $e->getMessage());
        }
    }
    public function getSavingsBalanceAttribute()
    {
        // Menjumlahkan semua setoran dan menguranginya dengan semua penarikan
        $setoran = $this->savings()->where('type', 'Setoran')->sum('amount');
        $penarikan = $this->savings()->where('type', 'Penarikan')->sum('amount');
        return $setoran - $penarikan;
    }

}

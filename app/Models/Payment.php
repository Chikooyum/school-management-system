<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_bill_id',
        'payment_date',
        'amount_paid',
        'payment_method',
        'receipt_number',
        'processed_by_user_id',
        'handover_user_id',
        'reconciled_at'
    ];

    /**
     * Relasi: Pembayaran ini tercatat untuk satu tagihan spesifik.
     */
    public function studentBill(): BelongsTo
    {
        return $this->belongsTo(StudentBill::class);
    }

    /**
     * Relasi: User yang memproses pembayaran (Sysadmin/Kepala Sekolah)
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    /**
     * Relasi: User yang harus menyetor uang (Guru)
     */
    public function handoverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handover_user_id');
    }

    // 🔧 HAPUS: Method user() lama sudah tidak diperlukan
    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'user_id');
    // }

    /**
     * Relasi untuk backward compatibility (jika masih ada kode yang menggunakan ->user)
     * Ini mengarah ke processor
     */
    public function user(): BelongsTo
    {
        return $this->processor();
    }
}

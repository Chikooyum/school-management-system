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
            'payment_method', // <-- Tambahkan ini

        'receipt_number',
        'user_id',
    ];

    /**
     * Relasi: Pembayaran ini tercatat untuk satu tagihan spesifik.
     */
    public function studentBill(): BelongsTo
    {
        return $this->belongsTo(StudentBill::class);
    }

    /**
     * Relasi: Pembayaran ini diproses oleh satu user (Sysadmin).
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

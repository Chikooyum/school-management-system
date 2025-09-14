<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSaving extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id', // <-- Tambahkan ini
        'transaction_date',
        'type',
        'amount',
        'description',
        'reconciled_at' // Pastikan ini juga ada
    ];

    /**
     * Relasi: Transaksi tabungan ini milik satu siswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi: Transaksi ini diproses oleh satu user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'cost_item_id',
        'due_date',
        'remaining_amount',
        'status',
    ];

    /**
     * Relasi: Tagihan ini milik satu siswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi: Tagihan ini mengacu pada satu item biaya.
     */
    public function costItem(): BelongsTo
    {
        return $this->belongsTo(CostItem::class);
    }

    /**
     * Relasi: Satu tagihan bisa memiliki banyak pembayaran (jika dicicil).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

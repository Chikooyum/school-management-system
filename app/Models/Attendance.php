<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_group_id',
        'attendance_date',
        'status',
        'notes',
        'recorded_by'
    ];

    /**
     * --- TAMBAHKAN FUNGSI-FUNGSI RELASI INI ---
     */

    /**
     * Mendefinisikan bahwa satu catatan absensi milik satu siswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Mendefinisikan bahwa satu catatan absensi milik satu kelas.
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /**
     * Mendefinisikan bahwa satu catatan absensi dicatat oleh satu user.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Absensi.php
class Absensi extends Model
{
    protected $fillable = [
        'teacher_id', 'student_id', 'status', 'tanggal', 'keterangan'
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'enrollment_year', 'staff_id'];

    public function waliKelas()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    // TAMBAHKAN FUNGSI INI
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}

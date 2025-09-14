<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'position', 'contact_info', 'user_id', 'photo_path'];

    /**
     * [TAMBAHKAN INI]
     * Menyertakan accessor photo_url ke dalam representasi JSON/array model.
     */
    protected $appends = ['photo_url'];

    /**
     * Mutator untuk atribut 'name'.
     */
    public function setNameAttribute($value)
    {
        $prefix = 'Tc. ';
        if (substr($value, 0, strlen($prefix)) !== $prefix) {
            $this->attributes['name'] = $prefix . $value;
        } else {
            $this->attributes['name'] = $value;
        }
    }

    /**
     * Mendefinisikan bahwa satu staf terhubung ke satu user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendefinisikan bahwa satu staf bisa mengajar banyak kelas (menjadi wali kelas).
     */
    public function classGroups()
    {
        return $this->hasMany(ClassGroup::class, 'staff_id');
    }

    /**
     * Accessor untuk mendapatkan URL lengkap foto.
     */
    public function getPhotoUrlAttribute()
{
    if ($this->photo_path) {
        // Gunakan asset() untuk URL yang lebih reliable
        return asset('storage/' . $this->photo_path);
    }
    return null;
}
}

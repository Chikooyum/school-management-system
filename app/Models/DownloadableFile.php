<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DownloadableFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'filename',
        'path',
        'user_id'
    ];

    // Accessor untuk mendapatkan URL file
    public function getUrlAttribute()
    {
        return Storage::url($this->path);
    }

    // Atau jika ingin full URL
    public function getFullUrlAttribute()
    {
        return asset(Storage::url($this->path));
    }
}

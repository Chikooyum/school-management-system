<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'item_code', 'category', 'location',
        'purchase_date', 'price', 'photo_path', 'status'
    ];

    // Accessor untuk URL foto
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo_path ? Storage::url($this->photo_path) : null,
        );
    }

    protected $appends = ['photo_url'];
}

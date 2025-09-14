<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cost_code',
        'type',
        'amount',
        'is_active',
    ];
    public function studentBills()
    {
        return $this->hasMany(StudentBill::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    protected $fillable = ['staff_id', 'attendance_date', 'check_in_time', 'recorded_by_user_id'];
}

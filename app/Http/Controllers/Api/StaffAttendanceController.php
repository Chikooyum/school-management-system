<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    // --- GANTI DENGAN TEKS RAHASIA ANDA SENDIRI ---
    private const PERMANENT_QR_TOKEN = 'IniAdalahKodeRahasiaAbsensiSekolahBintangPertiwi';

    // Untuk Sysadmin: Sekarang hanya mengambil token permanen
    public function getQrToken()
    {
        return response()->json(['token' => self::PERMANENT_QR_TOKEN]);
    }

    // Untuk Guru: Memvalidasi dengan token permanen
    public function checkIn(Request $request)
    {
        $validated = $request->validate(['token' => 'required|string']);
        $user = auth()->user();
        $staff = Staff::where('user_id', $user->id)->firstOrFail();
        $today = Carbon::today()->toDateString();

        // Cek apakah token yang di-scan cocok dengan token permanen kita
        if ($validated['token'] !== self::PERMANENT_QR_TOKEN) {
            return response()->json(['message' => 'QR Code tidak valid.'], 422);
        }

        $existing = StaffAttendance::where('staff_id', $staff->id)->where('attendance_date', $today)->exists();
        if ($existing) {
            return response()->json(['message' => 'Anda sudah berhasil absen hari ini.'], 409);
        }

        StaffAttendance::create([
            'staff_id' => $staff->id,
            'attendance_date' => $today,
            'check_in_time' => now()->toTimeString(),
            'recorded_by_user_id' => $user->id,
        ]);

        return response()->json(['message' => 'Absen berhasil! Selamat bekerja.']);
    }

    // Untuk Sysadmin: Mengambil laporan absensi guru
    public function getReport(Request $request)
    {
        $validated = $request->validate(['date' => 'required|date_format:Y-m-d']);
        $allStaff = Staff::where('position', 'like', '%Guru%')->get();
        $attendances = StaffAttendance::with('recorder:id,name')
            ->where('attendance_date', $validated['date'])->get()->keyBy('staff_id');

        $report = $allStaff->map(function ($staff) use ($attendances) {
            $attendanceRecord = $attendances->get($staff->id);
            return [
                'staff_name' => $staff->name,
                'status' => $attendanceRecord ? 'Hadir' : 'Tidak Hadir',
                'check_in_time' => $attendanceRecord?->check_in_time,
                'recorded_by' => $attendanceRecord?->recorder?->name,
            ];
        });

        return response()->json($report);
    }
}

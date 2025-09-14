<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassGroup;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Holiday; // <-- 1. Tambahkan import ini
 // <-- INI BARIS YANG HILANG

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    // Untuk GURU: Mengambil daftar siswa untuk absensi tanggal tertentu
    // app/Http/Controllers/Api/AttendanceController.php
// app/Http/Controllers/Api/AttendanceController.php
public function getTeacherClasses()
    {
        $user = auth()->user();
        $staff = Staff::where('user_id', $user->id)->firstOrFail();
        $classGroups = ClassGroup::where('staff_id', $staff->id)->get();
        return response()->json($classGroups);
    }


public function getAttendanceSheetForClass(Request $request, ClassGroup $classGroup)
    {
        // Otorisasi: Pastikan guru ini memang mengajar kelas yang diminta
        $user = auth()->user();
        if ($user->role === 'guru' && $classGroup->staff_id !== Staff::where('user_id', $user->id)->first()->id) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate(['date' => 'sometimes|date_format:Y-m-d']);
        $date = Carbon::parse($validated['date'] ?? now())->toDateString();

        $students = $classGroup->students()->orderBy('name')->get();
        $existingAttendance = Attendance::where('class_group_id', $classGroup->id)
            ->where('attendance_date', $date)->get()->keyBy('student_id');

        $students->each(function ($student) use ($existingAttendance) {
            $student->status = $existingAttendance[$student->id]->status ?? 'Hadir';
            $student->notes = $existingAttendance[$student->id]->notes ?? '';
        });

        return response()->json([
            'class_group' => $classGroup,
            'students' => $students,
            'is_existing_data' => $existingAttendance->isNotEmpty(),
        ]);
    }
    // Untuk GURU & SYSADMIN: Menyimpan data absensi
    public function storeAttendance(Request $request)
    {
        $data = $request->validate([
            'class_group_id' => 'required|exists:class_groups,id',
            'attendance_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:Hadir,Sakit,Izin,Alpa',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        $date = $data['attendance_date'];
        $recorderId = auth()->id();

        foreach ($data['attendances'] as $att) {
            Attendance::updateOrCreate(
                ['student_id' => $att['student_id'], 'attendance_date' => $date],
                [
                    'class_group_id' => $data['class_group_id'],
                    'status' => $att['status'],
                    'notes' => $att['notes'],
                    'recorded_by' => $recorderId,
                ]
            );
        }

        return response()->json(['message' => 'Absensi berhasil disimpan.']);
    }
    public function getPendingAttendanceClasses()
    {
        $today = Carbon::today();

        // Cek apakah hari ini Sabtu atau Minggu
        if ($today->isSaturday() || $today->isSunday()) {
            return response()->json([]); // Langsung kembalikan array kosong
        }

        // Cek apakah hari ini adalah hari libur yang terdaftar
        $isHoliday = Holiday::where('holiday_date', $today->toDateString())->exists();
        if ($isHoliday) {
            return response()->json([]); // Langsung kembalikan array kosong
        }

        // Jika bukan hari libur, lanjutkan dengan logika yang sudah ada
        $attendedClassIds = Attendance::where('attendance_date', $today->toDateString())->distinct()->pluck('class_group_id');

        $pendingClasses = ClassGroup::with('waliKelas:id,name')
                            ->whereNotIn('id', $attendedClassIds)
                            ->get();

        return response()->json($pendingClasses);
    }

    public function getAdminAttendanceSheet(Request $request, ClassGroup $classGroup)
    {
        $validated = $request->validate(['date' => 'sometimes|date_format:Y-m-d']);
        $date = Carbon::parse($validated['date'] ?? now())->toDateString();

        $students = $classGroup->students()->orderBy('name')->get();
        $existingAttendance = Attendance::where('class_group_id', $classGroup->id)
            ->where('attendance_date', $date)->get()->keyBy('student_id');

        $students->each(function ($student) use ($existingAttendance) {
            $student->status = $existingAttendance[$student->id]->status ?? 'Hadir';
            $student->notes = $existingAttendance[$student->id]->notes ?? '';
        });

        return response()->json([
            'class_group' => $classGroup,
            'students' => $students,
            'is_existing_data' => $existingAttendance->isNotEmpty(),
        ]);
    }

    // ... (method untuk Sysadmin tidak berubah) ...
}

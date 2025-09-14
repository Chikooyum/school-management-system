<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Tambahkan ini

class AbsensiController extends Controller
{
    public function getStudents()
    {
        try {
            $teacher = Auth::user();

            // Debug: Log teacher info
            Log::info('Teacher fetching students', [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'teacher_role' => $teacher->role
            ]);

            $students = Student::where('teacher_id', $teacher->id)->get();

            // Debug: Log students count
            Log::info('Students found', [
                'count' => $students->count(),
                'students' => $students->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $students
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching students', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data siswa: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'attendance' => 'required|array',
                'attendance.*.student_id' => 'required|exists:students,id',
                'attendance.*.status' => 'required|in:hadir,izin,sakit,alpha',
                'tanggal' => 'required|date'
            ]);

            $teacher = Auth::user();
            $tanggal = $request->tanggal;

            Log::info('Saving attendance', [
                'teacher_id' => $teacher->id,
                'tanggal' => $tanggal,
                'attendance' => $request->attendance
            ]);

            foreach ($request->attendance as $item) {
                // Verify that the student belongs to this teacher
                $student = Student::where('id', $item['student_id'])
                                  ->where('teacher_id', $teacher->id)
                                  ->first();

                if (!$student) {
                    Log::warning('Student not found or not assigned to teacher', [
                        'student_id' => $item['student_id'],
                        'teacher_id' => $teacher->id
                    ]);
                    continue; // Skip if student doesn't belong to this teacher
                }

                // Check if attendance record already exists
                $existing = Absensi::where('teacher_id', $teacher->id)
                    ->where('student_id', $item['student_id'])
                    ->where('tanggal', $tanggal)
                    ->first();

                if ($existing) {
                    // Update existing record
                    $existing->status = $item['status'];
                    $existing->keterangan = $item['keterangan'] ?? null;
                    $existing->save();

                    Log::info('Updated existing attendance', [
                        'attendance_id' => $existing->id,
                        'student_id' => $item['student_id'],
                        'status' => $item['status']
                    ]);
                } else {
                    // Create new record
                    $newAttendance = Absensi::create([
                        'teacher_id' => $teacher->id,
                        'student_id' => $item['student_id'],
                        'status' => $item['status'],
                        'keterangan' => $item['keterangan'] ?? null,
                        'tanggal' => $tanggal
                    ]);

                    Log::info('Created new attendance', [
                        'attendance_id' => $newAttendance->id,
                        'student_id' => $item['student_id'],
                        'status' => $item['status']
                    ]);
                }
            }

            return response()->json([
                'message' => 'Absensi berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving attendance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Gagal menyimpan absensi: ' . $e->getMessage()
            ], 500);
        }
    }
}

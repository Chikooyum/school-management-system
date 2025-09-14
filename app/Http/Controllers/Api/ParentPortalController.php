<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Attendance; // <-- Tambahkan ini di atas


class ParentPortalController extends Controller
{
    // GET /api/parent/me
    public function getStudentData(Request $request)
    {
        // $request->user() sekarang adalah Student model (bukan User)
        $student = $request->user()->load(['classGroup.waliKelas']);
        return response()->json($student);
    }

    // GET /api/parent/bills
    public function getBills(Request $request)
    {
        try {
            // $request->user() adalah Student model
            $student = $request->user();

            $bills = $student->bills()
                ->where('status', '!=', 'Lunas')
                ->with('costItem:id,name,amount')
                ->get();

            return response()->json($bills);

        } catch (\Exception $e) {
            \Log::error('Error in getBills:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/parent/history
    public function getPaymentHistory(Request $request)
    {
        try {
            // $request->user() adalah Student model
            $student = $request->user();

            $payments = Payment::whereHas('studentBill', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
            ->with('studentBill.costItem:id,name')
            ->latest('payment_date')
            ->get();

            return response()->json($payments);

        } catch (\Exception $e) {
            \Log::error('Error in getPaymentHistory:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getAttendanceSummary(Request $request)
    {
        $student = $request->user(); // Ingat, 'user' di sini adalah model Student

        // Ambil data absensi siswa ini untuk bulan dan tahun saat ini
        $attendances = Attendance::where('student_id', $student->id)
            ->whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->get();

        // Hitung jumlah untuk setiap status
        $summary = $attendances->groupBy('status')->map(fn ($group) => $group->count());

        return response()->json([
            'hadir' => $summary->get('Hadir', 0),
            'sakit' => $summary->get('Sakit', 0),
            'izin' => $summary->get('Izin', 0),
            'alpa' => $summary->get('Alpa', 0),
        ]);
    }
    public function getAttendanceDetails(Request $request)
{
    $student = $request->user();

    $attendances = Attendance::where('student_id', $student->id)
        ->whereMonth('attendance_date', now()->month)
        ->whereYear('attendance_date', now()->year)
        ->orderBy('attendance_date', 'asc')
        ->get();

    // Kelompokkan tanggal berdasarkan status
    $details = $attendances->groupBy('status')->map(function ($items) {
        return $items->map(fn ($item) => $item->attendance_date);
    });

    // Pastikan semua status ada sebagai key
    $allStatuses = ['Hadir', 'Sakit', 'Izin', 'Alpa'];
    foreach ($allStatuses as $status) {
        if (!$details->has($status)) {
            $details->put($status, []);
        }
    }

    return response()->json($details);
}
}


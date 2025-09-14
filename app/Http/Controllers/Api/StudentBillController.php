<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostItem;
use App\Models\Student;
use App\Models\StudentBill;
use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\ClassGroup;

class StudentBillController extends Controller
{
    /**
     * Menampilkan semua tagihan milik seorang siswa.
     * GET /api/students/{student}/bills
     */
    public function index(Student $student)
{
    $user = auth()->user();

    // --- LOGIKA PENGAMANAN BARU ---
    if ($user->role === 'guru') {
        $staff = Staff::where('user_id', $user->id)->first();

        // Cek apakah siswa yang diminta ada di dalam salah satu kelas yang diajar guru
        if (!$staff || !$staff->classGroups()->whereHas('students', function ($query) use ($student) {
            $query->where('id', $student->id);
        })->exists()) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat tagihan siswa ini.');
        }
    }
    // --- AKHIR LOGIKA PENGAMANAN ---

    $bills = $student->bills()->with(['costItem', 'payments'])->latest()->get();    return response()->json($bills);
}

    /**
     * Menetapkan sebuah tagihan ke satu atau beberapa siswa.
     * POST /api/bills/assign
     */
    public function assignBill(Request $request)
    {
        $request->validate([
            'cost_item_id' => 'required|exists:cost_items,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $costItem = CostItem::find($request->cost_item_id);
        $studentIds = $request->student_ids;
        $assignedCount = 0;

        foreach ($studentIds as $studentId) {
            // Cek agar tidak ada tagihan duplikat untuk item yg sama
            $existingBill = StudentBill::where('student_id', $studentId)
                                        ->where('cost_item_id', $costItem->id)
                                        ->first();

            if (!$existingBill) {
                StudentBill::create([
    'student_id' => $studentId,
    'cost_item_id' => $costItem->id,
    'remaining_amount' => $costItem->amount,
    'status' => 'Belum Lunas',
    'due_date' => $costItem->type === 'Dinamis' ? now()->addDays(30) : null
]);
                $assignedCount++;
            }
        }

        return response()->json([
            'message' => "Tagihan '{$costItem->name}' berhasil ditetapkan kepada {$assignedCount} siswa."
        ], 201);
    }
    // app/Http/Controllers/Api/StudentBillController.php

public function getUnpaidByCostItem(Request $request)
{
    $validated = $request->validate([
        'cost_item_id' => 'required|exists:cost_items,id'
    ]);

    $bills = StudentBill::with(['student:id,name,enrollment_year', 'costItem:id,name'])
        ->where('status', '!=', 'Lunas')
        ->where('cost_item_id', $validated['cost_item_id'])
        ->get();

    return response()->json($bills);
}
    public function destroy(StudentBill $studentBill)
    {
        // Pengaman: Hanya izinkan hapus jika statusnya "Belum Lunas"
        if ($studentBill->status !== 'Belum Lunas') {
            return response()->json(['message' => 'Hanya tagihan yang belum dibayar sama sekali yang bisa dihapus.'], 403); // 403 Forbidden
        }

        $studentBill->delete();

        return response()->noContent();
    }
}

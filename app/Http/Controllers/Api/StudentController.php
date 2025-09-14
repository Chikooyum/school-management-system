<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\CostItem;
use App\Models\StudentBill;
use App\Models\Staff;      // <-- Ditambahkan
use App\Models\ClassGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StudentController extends Controller
{
    // app/Http/Controllers/Api/StudentController.php

public function index(Request $request)
{
    $user = auth()->user();
    $query = Student::with('classGroup');

    if ($user->role === 'guru') {
        $staff = Staff::where('user_id', $user->id)->first();
        if ($staff) {
            $classIds = ClassGroup::where('staff_id', $staff->id)->pluck('id');
            $query->whereIn('class_group_id', $classIds);
        } else {
            return response()->json(['data' => []]);
        }
    }

    if ($request->query('all')) {
        return response()->json(['data' => $query->where('status', 'Aktif')->orderBy('name')->get()]);
    }
    if ($request->has('enrollment_year')) {
        $query->where('enrollment_year', $request->enrollment_year);
    }

    // --- LOGIKA SORTING BARU ---
    // Ambil parameter sorting dari request, dengan nilai default
    $sortBy = $request->query('sortBy', 'name');
    $sortOrder = $request->query('sortOrder', 'asc');

    // Daftar kolom yang diizinkan untuk di-sort demi keamanan
    $sortableColumns = ['name', 'enrollment_year', 'status'];

    if (in_array($sortBy, $sortableColumns)) {
        $query->orderBy($sortBy, $sortOrder);
    }
    // --- AKHIR LOGIKA SORTING ---

    $students = $query->paginate(100); // Kita masih pakai paginasi
    return response()->json($students);
}

    // Ganti seluruh method store() Anda dengan ini
public function store(Request $request)
{
    // 1. Validasi semua data yang masuk dari form
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'enrollment_year' => 'required|integer',
        'class_group_id' => 'nullable|exists:class_groups,id',
        'date_of_birth' => 'required|date',
        'mother_name' => 'required|string|max:255',
        'mother_date_of_birth' => 'required|date',
        'phone_number' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'registration_wave' => 'required|integer|min:1|max:3',
        'is_alumni_sibling' => 'boolean',
    ]);

    DB::beginTransaction();
    try {
        // 2. Buat user account untuk parent (jika ada rolenya)
        $parentUser = User::create([
            'name' => "Orang Tua " . $validatedData['name'],
            'email' => $this->generateParentEmail($validatedData['name'], $validatedData['phone_number'] ?? null),
            'role' => 'parent', // Pastikan role 'parent' ada di database Anda
            'password' => $this->generateParentPassword($validatedData['date_of_birth'], $validatedData['mother_date_of_birth']),
        ]);

        // 3. Siapkan data siswa, termasuk user_id dari parent
        $studentData = $validatedData;
        $studentData['user_id'] = $parentUser->id;
        $student = Student::create($studentData);

        // 4. Panggil logika penagihan otomatis dari Model Student
        $student->createInitialBills();

        DB::commit();

        return response()->json(['message' => 'Siswa berhasil ditambahkan beserta tagihan awalnya.'], 201);

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Student creation failed: ' . $e->getMessage());
        return response()->json(['error' => 'Gagal menambahkan siswa.'], 500);
    }
}

    // ... (method show, update, destroy, promoteStudents tidak berubah) ...
    public function show(Student $student) { /* ... */ }
    // app/Http/Controllers/Api/StudentController.php

public function update(Request $request, Student $student)
{
    // 1. Validasi semua field yang mungkin diubah dari form
    $validatedData = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'enrollment_year' => 'sometimes|required|integer',
        'class_group_id' => 'sometimes|nullable|exists:class_groups,id',
        'date_of_birth' => 'sometimes|required|date',
        'mother_name' => 'sometimes|required|string|max:255',
        'mother_date_of_birth' => 'sometimes|required|date',
        'phone_number' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'registration_wave' => 'sometimes|required|integer|min:1|max:3',
        'is_alumni_sibling' => 'sometimes|boolean',
        'status' => 'sometimes|required|in:Aktif,Alumni,Cuti',
    ]);

    // 2. Update siswa hanya dengan data yang sudah divalidasi
    $student->update($validatedData);

    return response()->json($student->load('classGroup'));
}
    public function destroy(string $id)
{
    // Cari siswa secara manual berdasarkan ID dari URL
    $student = Student::find($id);

    // Jika siswa tidak ditemukan, kirim error 404
    if (!$student) {
        return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
    }

    // Jika ditemukan, hapus
    $student->delete();

    // Kirim response sukses
    return response()->json(null, 204);
}
    public function promoteStudents(Request $request) { /* ... */ }

    // --- FUNGSI HELPER (DARI KODE ANDA) ---
    private function generateParentEmail($studentName, $phoneNumber)
    {
        $cleanName = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $studentName));
        if ($phoneNumber) {
            return $cleanName . '.' . preg_replace('/[^0-9]/', '', $phoneNumber) . '@parent.school';
        }
        return $cleanName . '.' . time() . '@parent.school';
    }

    private function generateParentPassword($childDob, $motherDob)
    {
        $childDate = Carbon::parse($childDob)->format('dmY');
        $motherDate = Carbon::parse($motherDob)->format('dmY');
        return Hash::make($childDate . $motherDate);
    }
}

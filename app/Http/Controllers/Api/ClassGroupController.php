<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- 1. Tambahkan ini

class ClassGroupController extends Controller
{
    public function index()
    {
        return ClassGroup::with('waliKelas:id,name')->withCount('students')->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'enrollment_year' => 'required|digits:4'
        ]);
        $classGroup = ClassGroup::create($validated);
        return $classGroup->load('waliKelas:id,name');
    }

    public function update(Request $request, ClassGroup $classGroup)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'enrollment_year' => 'sometimes|digits:4',
            'staff_id' => 'nullable|exists:staff,id'
        ]);
        $classGroup->update($validated);
        return $classGroup->load('waliKelas:id,name');
    }

    // --- 2. PERBAIKAN UTAMA DI SINI ---
    public function destroy(ClassGroup $classGroup)
    {
        DB::transaction(function () use ($classGroup) {
            // Lepaskan semua siswa dari kelas ini terlebih dahulu
            Student::where('class_group_id', $classGroup->id)->update(['class_group_id' => null]);

            // Baru hapus kelasnya
            $classGroup->delete();
        });

        return response()->noContent();
    }

    public function assignStudents(Request $request, ClassGroup $classGroup)
    {
        $validated = $request->validate([
            'student_ids' => 'present|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        DB::transaction(function () use ($validated, $classGroup) {
            // Lepaskan siswa lama HANYA jika mereka tidak ada di daftar baru
            Student::where('class_group_id', $classGroup->id)
                   ->whereNotIn('id', $validated['student_ids'])
                   ->update(['class_group_id' => null]);

            // Tetapkan siswa yang baru dipilih
            if (!empty($validated['student_ids'])) {
                Student::whereIn('id', $validated['student_ids'])->update(['class_group_id' => $classGroup->id]);
            }
        });

        return response()->json(['message' => 'Daftar siswa di kelas berhasil diperbarui.']);
    }
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    // Atau jika sudah ada waliKelas relationship:
    public function waliKelas()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}

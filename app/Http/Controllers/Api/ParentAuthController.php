<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ParentAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'date_of_birth' => 'required|date',
            'mother_date_of_birth' => 'required|date',
        ]);

        try {
            // Cari student berdasarkan data yang diinput parent
            $student = Student::where('name', $request->name)
                ->where('date_of_birth', $request->date_of_birth)
                ->where('mother_date_of_birth', $request->mother_date_of_birth)
                ->with(['classGroup.waliKelas']) // Load relasi yang dibutuhkan
                ->first();

            if (!$student) {
                return response()->json([
                    'error' => 'Data tidak ditemukan. Pastikan nama dan tanggal lahir sudah benar.'
                ], 404);
            }

            // Buat token untuk student (gunakan Student model yang sudah punya HasApiTokens)
            $token = $student->createToken('parent-access')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'student' => $student,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            \Log::error('Parent login error:', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Terjadi kesalahan saat login'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }
}

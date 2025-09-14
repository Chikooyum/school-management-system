<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
// DIHAPUS: Import yang tidak lagi digunakan di controller ini
// use App\Models\User;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    // app/Http/Controllers/Api/StaffController.php

// Ganti method index() Anda menjadi seperti ini:
public function index()
{
    // Gunakan with('user') untuk mengambil data staf beserta data akunnya
    return Staff::with('user')->orderBy('name', 'asc')->get();
}

    /**
     * Menyimpan data staf baru (khusus untuk staf non-login).
     * Pembuatan user untuk guru/staf lain sekarang terpusat di UserController.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:staff,name',
            'position' => 'required|string|max:255',
            'contact_info' => 'nullable|string',
        ]);

        // Pastikan tidak ada user_id yang dikirim dari form ini
        unset($validated['user_id']);

        $staff = Staff::create($validated);

        return response()->json($staff, 201);
    }

    /**
     * Memperbarui data staf.
     */
    public function update(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:staff,name,' . $staff->id,
            'position' => 'required|string|max:255',
            'contact_info' => 'nullable|string',
        ]);

        $staff->update($validated);
        return response()->json($staff->load('user'));
    }

    /**
     * Menghapus data staf.
     */
    public function destroy(Staff $staff)
    {
        // Tetap hapus user yang terhubung jika ada, untuk kasus data lama
        if ($staff->user) {
            $staff->user->delete();
        }

        $staff->delete();
        return response()->noContent();
    }
}

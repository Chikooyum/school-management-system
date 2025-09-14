<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage; // <-- Jangan lupa import Storage


class TeacherPortalController extends Controller
{
    // Method untuk mengubah data profil (nama & kontak)
    public function updateProfile(Request $request)
{
    $user = $request->user();
    $staff = Staff::where('user_id', $user->id)->firstOrFail();

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'contact_info' => 'nullable|string|max:255',
    ]);

    $staff->update($validated);
    $user->update(['name' => $validated['name']]);

    // --- TAMBAHKAN BARIS INI ---
    // Muat ulang data staff yang terhubung dengan user
    $user->load('staff');

    return response()->json(['message' => 'Profil berhasil diperbarui.', 'user' => $user]);
}

    // Method untuk mengubah password
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }
    public function updatePhoto(Request $request)
{
    $request->validate([
        'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi: harus gambar, maks 2MB
    ]);

    $user = $request->user();
    $staff = $user->staff; // Asumsi ada relasi 'staff' di model User

    if (!$staff) {
        return response()->json(['message' => 'Profil staf tidak ditemukan'], 404);
    }

    // Hapus foto lama jika ada
    if ($staff->photo_path) {
        Storage::disk('public')->delete($staff->photo_path);
    }

    // Simpan foto baru dan dapatkan path-nya
    $path = $request->file('photo')->store('profile-photos', 'public');

    // Update path di database
    $staff->update(['photo_path' => $path]);

    // Muat ulang data user dengan relasi staff yang sudah terupdate
    $user->load('staff');

    return response()->json([
        'message' => 'Foto profil berhasil diperbarui!',
        'user' => $user, // Kirim kembali data user yang terupdate
    ]);
}
}

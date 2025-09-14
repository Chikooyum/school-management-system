<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff; // <-- Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth; // <-- INI BARIS YANG HILANG
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    // Method baru untuk mendapatkan peran yang bisa dibuat
    public function getCreatableRoles()
    {
        $user = Auth::user();
        $roles = [];
        if ($user->role === 'superadmin') {
            $roles = ['sysadmin', 'admin'];
        } elseif ($user->role === 'sysadmin') {
            $roles = ['admin', 'guru'];
        }
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $creatableRoles = [];
        if ($user->role === 'superadmin') {
            $creatableRoles = ['sysadmin', 'admin'];
        } elseif ($user->role === 'sysadmin') {
            $creatableRoles = ['admin', 'guru'];
        }

        if (empty($creatableRoles) || !in_array($request->input('role'), $creatableRoles)) {
            return response()->json(['message' => 'Anda tidak memiliki hak untuk membuat user dengan peran ini.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => ['required', Rule::in($creatableRoles)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $newUser = User::create($validated);

        // --- LOGIKA BARU DITAMBAHKAN DI SINI ---
        // Jika peran yang baru dibuat adalah 'guru', buat juga data stafnya
        if ($newUser->role === 'guru') {
            Staff::create([
                'name' => $newUser->name,
                'position' => 'Guru', // Jabatan default
                'user_id' => $newUser->id, // Hubungkan ke user yang baru dibuat
            ]);
        }
        // --- AKHIR LOGIKA BARU ---

        return response()->json($newUser, 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
        ]);
        $user->update($validated);
        return response()->json($user);
    }

    // app/Http/Controllers/Api/UserController.php

public function destroy(User $user)
{
    // Logika ini sudah benar, untuk mencegah user menghapus diri sendiri.
    if ($user->id === auth()->id()) {
        return response()->json(['message' => 'Anda tidak bisa menghapus akun Anda sendiri.'], 403);
    }

    // [TAMBAHKAN INI]
    // Sebelum menghapus user, periksa apakah dia punya profil staf.
    // Jika ada, hapus profil stafnya terlebih dahulu.
    if ($user->staff) {
        $user->staff->delete();
    }

    // Setelah profil staf (jika ada) dihapus, baru hapus user-nya.
    $user->delete();

    // Kirim respons berhasil.
    return response()->noContent();
}

    public function changePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }
}

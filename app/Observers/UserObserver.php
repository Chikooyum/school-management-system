<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Log; // <-- TAMBAHKAN INI


class UserObserver
{
    /**
     * Handle the User "created" event.
     * Dijalankan setelah user baru dibuat.
     */
    public function created(User $user): void
    {
        // Logika ini sudah benar, untuk membuat staf baru secara otomatis.
        if ($user->role === 'wali' || $user->role === 'admin' || $user->role === 'sysadmin' || $user->role ==='superadmin') {
            Staff::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => ucfirst($user->role),
            ]);
        }
    }

    /**
     * [TAMBAHKAN INI]
     * Handle the User "deleted" event.
     * Dijalankan setelah user dihapus.
     */
    public function deleted(User $user): void
    {
        // [TAMBAHKAN LOG INI UNTUK TES]
        Log::info('USER DELETED EVENT FIRED FOR USER ID: ' . $user->id);

        if ($user->staff) {
            $user->staff->delete();
            Log::info('STAFF PROFILE DELETED FOR USER ID: ' . $user->id); // Log jika berhasil
        } else {
            Log::info('NO STAFF PROFILE FOUND FOR USER ID: ' . $user->id); // Log jika tidak ada
        }
    }
}

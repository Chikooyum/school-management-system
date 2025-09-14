<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    // Mengambil semua pengumuman, diurutkan dari yang terbaru
    public function index()
    {
        return Announcement::latest()->get();
    }

    // Menyimpan pengumuman baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
                'user_id' => auth()->id(), // <-- PERBAIKAN

        ]);

        return response()->json($announcement, 201);
    }

    // Menghapus pengumuman
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return response()->json(null, 204);
    }
}

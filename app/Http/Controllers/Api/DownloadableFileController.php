<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DownloadableFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadableFileController extends Controller
{
    public function index()
{
    $files = DownloadableFile::latest()->get();

    $files->each(function ($file) {
        // Gunakan route download instead of direct storage URL
        $filename = basename($file->path);
        $file->url = url("api/download/{$filename}");
    });

    return $files;
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ]);

        $path = $request->file('file')->store('public/downloads');

        $file = DownloadableFile::create([
            'title' => $validated['title'],
            'filename' => $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'user_id' => 1, // Ganti dengan auth()->id()
        ]);

        // Tambahkan FULL URL saat response
        $file->url = asset(Storage::url($file->path));

        return response()->json($file, 201);
    }

    public function destroy(DownloadableFile $downloadableFile)
    {
        Storage::delete($downloadableFile->path);
        $downloadableFile->delete();

        return response()->json(null, 204);
    }
    public function download($filename)
{
    $path = 'public/downloads/' . $filename;

    if (!Storage::exists($path)) {
        abort(404, 'File not found');
    }

    return Storage::download($path);
}
}

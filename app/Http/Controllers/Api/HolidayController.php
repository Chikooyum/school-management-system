<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('holiday_date')->get();
        return response()->json($holidays);
    }

    public function store(Request $request)
{
    Log::info('=== HOLIDAY STORE DEBUG ===');
        Log::info('Request data:', $request->all());
    $validated = $request->validate([
        'title' => 'nullable|string|max:255',        // <-- UBAH JADI NULLABLE
            'holiday_date' => 'required|date_format:Y-m-d', // PERBAIKI: Harus format Y-m-d
        'description' => 'nullable|string|max:1000',
    ]);
            Log::info('Validated data:', $validated);
    $existingHoliday = Holiday::where('holiday_date', $validated['holiday_date'])->first();
        if ($existingHoliday) {
            Log::warning('Holiday already exists:', ['existing' => $existingHoliday]);
            return response()->json([
                'message' => 'Hari libur untuk tanggal ini sudah ada.',
                'errors' => ['holiday_date' => ['Tanggal sudah terdaftar sebagai hari libur.']]
            ], 422);
        }

        $holiday = Holiday::create($validated);
        Log::info('Holiday created:', ['holiday' => $holiday]);

        return response()->json([
            'message' => 'Hari libur berhasil ditambahkan',
            'holiday' => $holiday
        ], 201);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return response()->json(['message' => 'Holiday deleted successfully']);
    }

    // Method untuk check apakah tanggal tertentu adalah hari libur
    public function checkDate(Request $request)
    {
        Log::info('=== HOLIDAY CHECK DEBUG ===');
        Log::info('Request params:', $request->all());

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        Log::info('Checking date:', ['date' => $validated['date']]);

        $holiday = Holiday::where('holiday_date', $validated['date'])->first();

        Log::info('Holiday found:', ['holiday' => $holiday]);

        $result = [
            'is_holiday' => !!$holiday,
            'description' => $holiday ? $holiday->title : null,
            'holiday' => $holiday
        ];

        Log::info('Check result:', $result);

        return response()->json($result);
    }
}

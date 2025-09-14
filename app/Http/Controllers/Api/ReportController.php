<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentBill;
use App\Models\StudentSaving; // <-- Tambahkan
use App\Models\Attendance; // <-- Tambahkan ini di atas

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // <-- INI BARIS YANG HILANG
// <-- INI BARIS YANG HILANG
 // <-- Tambahkan ini

class ReportController extends Controller
{
    public function getMonthlyIncomeDetails()
    {
        $payments = Payment::with(['studentBill.student:id,name', 'studentBill.costItem:id,name'])
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->latest('payment_date')->get();

        return response()->json($payments);
    }

    public function getArrearsDetails()
    {
        $bills = StudentBill::with(['student:id,name', 'costItem:id,name'])
            ->where('status', '!=', 'Lunas')
            ->orderBy('remaining_amount', 'desc')
            ->get();

        return response()->json($bills);
    }

    public function getActiveStudents()
    {
        return Student::where('status', 'Aktif')->orderBy('name')->get();
    }

    // app/Http/Controllers/Api/ReportController.php
public function getHandoverReport(Request $request)
    {

        $reportDate = $request->query('date', now()->toDateString());

        // --- LOGIKA BARU UNTUK LAPORAN TERTUNDA ---
        $pendingPayments = Payment::with('studentBill.student', 'studentBill.costItem', 'user')->whereNull('reconciled_at')->get();
        $pendingSavings = StudentSaving::with('student', 'user')->where('type', 'Setoran')->whereNull('reconciled_at')->get();

        $pendingReport = collect([]);
        foreach ($pendingPayments as $p) {
    $handoverUser = \App\Models\User::find($p->handover_user_id);
    if($handoverUser) $pendingReport->push(['user_id' => $handoverUser->id, 'user_name' => $handoverUser->name, 'type' => 'Pembayaran', 'description' => $p->studentBill->costItem->name . ' - ' . $p->studentBill->student->name, 'amount' => $p->amount_paid, 'created_at' => $p->created_at, 'method' => $p->payment_method]);
}
        foreach ($pendingSavings as $s) {
    $handoverUser = \App\Models\User::find($s->handover_user_id);
    if($handoverUser) $pendingReport->push(['user_id' => $handoverUser->id, 'user_name' => $handoverUser->name, 'type' => 'Tabungan', 'description' => 'Setoran Tabungan - ' . $s->student->name, 'amount' => $s->amount, 'created_at' => $s->created_at, 'method' => 'Tunai']);
}

        // Kelompokkan berdasarkan tanggal, LALU berdasarkan user
        $groupedPending = $pendingReport->groupBy(function($item) {
            return Carbon::parse($item['created_at'])->toDateString();
        })->flatMap(function($itemsByDate, $date) {
            return $itemsByDate->groupBy('user_name')->map(function($itemsByUser, $name) use ($date) {
                return [
                    'user_id' => $itemsByUser->first()['user_id'],
                    'user_name' => $name,
                    'report_date' => $date, // Tambahkan tanggal laporan
                    'total_amount' => $itemsByUser->filter(function ($item) {
    return $item['type'] === 'Tabungan' || ($item['type'] === 'Pembayaran' && $item['method'] === 'Tunai');
})->sum('amount'),
                    'details' => $itemsByUser->sortBy('created_at')->values()->all(),
                ];
            });
        })->sortBy('report_date')->values();


        // --- LOGIKA RIWAYAT (TIDAK BERUBAH BANYAK) ---
        $reconciledPayments = Payment::with('studentBill.student', 'studentBill.costItem', 'user')->whereDate('reconciled_at', $reportDate)->get();
        $reconciledSavings = StudentSaving::with('student', 'user')->where('type', 'Setoran')->whereDate('reconciled_at', $reportDate)->get();

        $reconciledReport = collect([]);
        foreach ($reconciledPayments as $p) {
    $handoverUser = \App\Models\User::find($p->handover_user_id);
    if($handoverUser) $reconciledReport->push(['user_id' => $handoverUser->id, 'user_name' => $handoverUser->name, 'type' => 'Pembayaran', 'description' => $p->studentBill->costItem->name . ' - ' . $p->studentBill->student->name, 'amount' => $p->amount_paid, 'created_at' => $p->created_at, 'method' => $p->payment_method]);
}
        foreach ($reconciledSavings as $s) {
    $handoverUser = \App\Models\User::find($s->handover_user_id);
    if($handoverUser) $reconciledReport->push(['user_id' => $handoverUser->id, 'user_name' => $handoverUser->name, 'type' => 'Tabungan', 'description' => 'Setoran Tabungan - ' . $s->student->name, 'amount' => $s->amount, 'created_at' => $s->created_at, 'method' => 'Tunai']);
}

        $groupedReconciled = $reconciledReport->groupBy('user_name')->map(function ($items, $name) {
    // Hitung hanya uang tunai (Setoran Tabungan + Pembayaran Tunai)
    $cashTotal = $items->filter(function ($item) {
        return $item['type'] === 'Tabungan' || ($item['type'] === 'Pembayaran' && $item['method'] === 'Tunai');
    })->sum('amount');

    return [
        'user_id' => $items->first()['user_id'],
        'user_name' => $name,
        'total_amount' => $cashTotal,
        'details' => $items->sortBy('created_at')->values()->all(),
    ];
})->values();

        return response()->json([
            'pending_reports' => $groupedPending,
            'reconciled_reports' => $groupedReconciled,
        ]);
    }

    public function reconcileTransactions(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'report_date' => 'required|date_format:Y-m-d',
    ]);

    DB::transaction(function () use ($validated) {
        // Hapus baris $reconciledAt = ...

        // Gunakan now() langsung, yang akan mencatat tanggal & waktu saat ini
        Payment::where('handover_user_id', $validated['user_id'])
            ->whereDate('created_at', $validated['report_date'])
            ->whereNull('reconciled_at')
            ->update(['reconciled_at' => $validated['report_date']]);

        StudentSaving::where('handover_user_id', $validated['user_id'])
            ->where('type', 'Setoran')
            ->whereDate('created_at', $validated['report_date'])
            ->whereNull('reconciled_at')
            ->update(['reconciled_at' => $validated['report_date']]);
    });

    return response()->json(['message' => 'Laporan berhasil direkonsiliasi.']);
}
    // app/Http/Controllers/Api/ReportController.php
public function getAttendanceReport(Request $request)
{
    $validated = $request->validate(['date' => 'required|date_format:Y-m-d']);
    $date = $validated['date'];

    $attendances = Attendance::with(['student:id,name', 'classGroup:id,name'])
                    ->where('attendance_date', $date)
                    ->get();

    // 1. Buat data ringkasan (summary)
    $summary = $attendances->groupBy('status')->map(fn ($group) => $group->count());

    // 2. Buat daftar terpisah untuk setiap status absensi
    $mapToResponse = function ($item) {
        return [
            'student_name' => $item->student->name,
            'class_name' => $item->classGroup->name,
            'notes' => $item->notes,
        ];
    };

    $sakitList = $attendances->where('status', 'Sakit')->map($mapToResponse)->values();
    $izinList = $attendances->where('status', 'Izin')->map($mapToResponse)->values();
    $alpaList = $attendances->where('status', 'Alpa')->map($mapToResponse)->values();

    return response()->json([
        'summary' => [
            'hadir' => $summary->get('Hadir', 0),
            'sakit' => $summary->get('Sakit', 0),
            'izin' => $summary->get('Izin', 0),
            'alpa' => $summary->get('Alpa', 0),
        ],
        'sakit_list' => $sakitList,
        'izin_list' => $izinList,
        'alpa_list' => $alpaList,
    ]);
}
public function getMonthlyAttendanceReport(Request $request)
{
    $validated = $request->validate([
        'month' => 'required|integer|min:1|max:12',
        'year' => 'required|integer|min:2024',
    ]);
    $month = $validated['month'];
    $year = $validated['year'];

    $attendances = Attendance::with('student:id,name')
        ->whereMonth('attendance_date', $month)
        ->whereYear('attendance_date', $year)
        ->get();

    // 1. Ringkasan Total
    $summary = $attendances->groupBy('status')->map(fn ($group) => $group->count());

    // 2. Tren Harian (jumlah yang hadir per hari)
    $dailyTrend = $attendances->where('status', 'Hadir')
        ->groupBy(function($item) {
            return Carbon::parse($item->attendance_date)->format('d');
        })
        ->map(fn ($group) => $group->count());

    // 3. Rincian per Siswa
    $studentDetails = $attendances->groupBy('student.name')
        ->map(function ($items, $name) {
            $statusCounts = $items->groupBy('status')->map(fn ($group) => $group->count());
            return [
                'student_name' => $name,
                'hadir' => $statusCounts->get('Hadir', 0),
                'sakit' => $statusCounts->get('Sakit', 0),
                'izin' => $statusCounts->get('Izin', 0),
                'alpa' => $statusCounts->get('Alpa', 0),
            ];
        })->sortBy('student_name')->values();

    return response()->json([
        'summary' => [
            'hadir' => $summary->get('Hadir', 0),
            'sakit' => $summary->get('Sakit', 0),
            'izin' => $summary->get('Izin', 0),
            'alpa' => $summary->get('Alpa', 0),
        ],
        'daily_trend' => $dailyTrend,
        'student_details' => $studentDetails,
    ]);
}

public function getAllPayments(Request $request)
{
        $user = auth()->user(); // Ambil user yang sedang login

    $range = $request->query('range', '1_month'); // Default 1 bulan

    $query = Payment::with(['studentBill.student:id,name', 'processor:id,name'])
                    ->orderBy('payment_date', 'desc');

    if ($user->role === 'guru') {
    $query->where('handover_user_id', $user->id);
}

    // Terapkan filter rentang waktu
    if ($range === '1_month') {
        $query->where('payment_date', '>=', now()->subMonth());
    } elseif ($range === '3_months') {
        $query->where('payment_date', '>=', now()->subMonths(3));
    }
    // Jika 'all', tidak ada filter waktu

    $payments = $query->paginate(20);

    // Ambil semua nomor kwitansi yang muncul lebih dari sekali
    $multiBillReceiptNumbers = Payment::select('receipt_number')
        ->whereIn('receipt_number', $payments->pluck('receipt_number'))
        ->groupBy('receipt_number')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('receipt_number');

    // Tambahkan penanda 'is_multi_bill' pada setiap item pembayaran
    $payments->getCollection()->transform(function ($payment) use ($multiBillReceiptNumbers) {
        $payment->is_multi_bill = $multiBillReceiptNumbers->contains($payment->receipt_number);
        return $payment;
    });

    return response()->json($payments);
}
}

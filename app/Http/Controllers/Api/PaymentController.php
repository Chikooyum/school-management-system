<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\StudentBill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf; // Import PDF
use Illuminate\Support\Facades\Log; // <-- INI BARIS YANG HILANG


class PaymentController extends Controller
{
    /**
     * Memproses pembayaran baru.
     * POST /api/payments
     */
    // app/Http/Controllers/Api/PaymentController.php

public function store(Request $request)
    {
        $validated = $request->validate([
            'student_bill_id' => 'required|exists:student_bills,id',
            'amount_paid' => 'required|numeric|min:1',
            'receipt_number' => 'required|string|unique:payments,receipt_number',
            'on_behalf_of_user_id' => 'nullable|exists:users,id',
        ]);

        $bill = StudentBill::with('student.classGroup.waliKelas.user')->findOrFail($validated['student_bill_id']);
        $student = $bill->student;

        if ($validated['amount_paid'] > $bill->remaining_amount) {
            return response()->json(['message' => 'Jumlah bayar melebihi sisa tagihan.'], 422);
        }

        DB::beginTransaction();
        try {
            $actor = auth()->user();
            $waliKelasUser = $student->classGroup?->waliKelas?->user;
            $handoverUserId = (($actor->role === 'sysadmin' || $actor->role === 'superadmin') && $waliKelasUser) ? $waliKelasUser->id : $actor->id;

            $payment = Payment::create([
                'student_bill_id' => $bill->id,
                'payment_date' => now(),
                'amount_paid' => $validated['amount_paid'],
                'receipt_number' => $validated['receipt_number'],
                'payment_method' => 'Tunai',
                'processed_by_user_id' => $actor->id,
                'handover_user_id' => $handoverUserId,
            ]);

            $bill->remaining_amount -= $validated['amount_paid'];
            if ($bill->remaining_amount <= 0) { $bill->status = 'Lunas'; } else { $bill->status = 'Cicilan'; }
            $bill->save();

            DB::commit();

            return response()->json($payment);

        } catch (\Exception $e) {
            DB::rollBack();
            // Sekarang Log::error() akan berfungsi
            Log::error('Payment processing failed: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat memproses pembayaran.'], 500);
        }
    }
    public function generateReceipt(Payment $payment)
{
    // Tambahkan 'user' untuk mengambil data pemroses pembayaran
    $payment->load('studentBill.student', 'studentBill.costItem', 'processor');
    $data = ['payment' => $payment];
    $pdf = Pdf::loadView('receipts.default', $data);
    return $pdf->stream('kwitansi-' . $payment->receipt_number . '.pdf');
}
    public function storeBulk(Request $request)
{
    $validated = $request->validate([
        'student_bill_ids' => 'required|array',
        'student_bill_ids.*' => 'exists:student_bills,id',
    ]);

    $processedCount = 0;
    DB::beginTransaction();
    try {
        // --- LOGIKA BARU UNTUK NOMOR URUT ANGKA ---
        // 1. Ambil nomor kwitansi terakhir (sebagai angka)
        $lastNumber = \App\Models\Payment::max(DB::raw('CAST(receipt_number AS UNSIGNED)'));

        // 2. Tentukan nomor berikutnya, jika belum ada, mulai dari 10001
        $nextNumber = ($lastNumber > 0) ? $lastNumber + 1 : 10001;

        foreach ($validated['student_bill_ids'] as $billId) {
            $bill = \App\Models\StudentBill::find($billId);
            if ($bill && $bill->status !== 'Lunas') {
                $actor = auth()->user();
Payment::create([
    'student_bill_id' => $bill->id,
    'payment_date' => now(),
    'amount_paid' => $bill->remaining_amount,
    'receipt_number' => $nextNumber,
    'payment_method' => 'Tunai',
    'processed_by_user_id' => $actor->id,
    'handover_user_id' => $actor->id,
]);

                $bill->remaining_amount = 0;
                $bill->status = 'Lunas';
                $bill->save();

                $processedCount++;
                $nextNumber++; // Naikkan nomor untuk siswa berikutnya
            }
        }
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Terjadi kesalahan saat pemrosesan.', 'error' => $e->getMessage()], 500);
    }

    return response()->json(['message' => "$processedCount pembayaran berhasil diproses."]);
}
// app/Http/Controllers/Api/PaymentController.php
public function getLatestReceiptNumber()
{
    $latestPayment = \App\Models\Payment::latest('id')->first();
    return response()->json([
        'latest_receipt_number' => $latestPayment?->receipt_number
    ]);
}
// app/Http/Controllers/Api/PaymentController.php

public function storeMultiBill(Request $request)
{
    $validated = $request->validate([
        'student_bill_ids' => 'required|array|min:1',
        'student_bill_ids.*' => 'exists:student_bills,id',
        'receipt_number' => 'required|string',
    ]);

    DB::beginTransaction();
    try {
        $bills = StudentBill::with('student.classGroup.waliKelas.user') // Muat relasi
            ->whereIn('id', $validated['student_bill_ids'])->get();

        if ($bills->isEmpty()) {
            return response()->json(['message' => 'Tagihan tidak ditemukan.'], 404);
        }
        if ($bills->pluck('student_id')->unique()->count() > 1) {
            return response()->json(['message' => 'Semua tagihan harus milik siswa yang sama.'], 422);
        }

        $actor = auth()->user();
        $student = $bills->first()->student;

        // --- LOGIKA OTOMATIS DITAMBAHKAN DI SINI ---
        $waliKelasUser = $student->classGroup?->waliKelas?->user;
        $handoverUserId = (($actor->role === 'sysadmin' || $actor->role === 'superadmin') && $waliKelasUser)
            ? $waliKelasUser->id
            : $actor->id;

        foreach ($bills as $bill) {
            if ($bill->status !== 'Lunas') {
                Payment::create([
                    'student_bill_id' => $bill->id,
                    'payment_date' => now(),
                    'amount_paid' => $bill->remaining_amount,
                    'receipt_number' => $validated['receipt_number'],
                    'payment_method' => 'Tunai',
                    'processed_by_user_id' => $actor->id,
                    'handover_user_id' => $handoverUserId, // Gunakan ID yang sudah ditentukan
                ]);
                $bill->update(['remaining_amount' => 0, 'status' => 'Lunas']);
            }
        }
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Terjadi kesalahan saat pemrosesan.', 'error' => $e->getMessage()], 500);
    }

    return response()->json(['message' => 'Pembayaran berhasil diproses.', 'receipt_number' => $validated['receipt_number']]);
}
// Method baru untuk mencetak kwitansi gabungan
public function generateMultiBillReceipt($receipt_number)
{
    $payments = Payment::with('studentBill.student', 'studentBill.costItem', 'processor')
        ->where('receipt_number', $receipt_number)
        ->get();

    if ($payments->isEmpty()) {
        abort(404, 'Kwitansi tidak ditemukan.');
    }

    $data = ['payments' => $payments]; // Kirim collection of payments
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('receipts.default', $data);
    return $pdf->stream('kwitansi-' . $receipt_number . '.pdf');
}
}

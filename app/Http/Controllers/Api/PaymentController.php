<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\StudentBill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf; // Import PDF

class PaymentController extends Controller
{
    /**
     * Memproses pembayaran baru.
     * POST /api/payments
     */
    // app/Http/Controllers/Api/PaymentController.php

public function store(Request $request)
{
    // 1. Tambahkan 'receipt_number' di validasi
    // Aturan 'unique:payments' memastikan tidak ada nomor kwitansi yang sama
    $request->validate([
        'student_bill_id' => 'required|exists:student_bills,id',
        'amount_paid' => 'required|numeric|min:1',
        'receipt_number' => 'required|string|unique:payments,receipt_number',
    ]);

    $bill = StudentBill::findOrFail($request->student_bill_id);

    if ($request->amount_paid > $bill->remaining_amount) {
        return response()->json(['message' => 'Jumlah bayar melebihi sisa tagihan.'], 422);
    }

    try {
        DB::beginTransaction();

        // 2. Gunakan 'receipt_number' dari request, bukan generate otomatis
        $payment = Payment::create([
            'student_bill_id' => $bill->id,
            'payment_date' => now(),
            'amount_paid' => $request->amount_paid,
            'receipt_number' => $request->receipt_number, // <-- PERUBAHAN DI SINI
            'user_id' => auth()->id(),
        ]);

        $bill->remaining_amount -= $request->amount_paid;

        if ($bill->remaining_amount <= 0) { // Gunakan <= untuk mengatasi floating point issue
            $bill->status = 'Lunas';
        } else {
            $bill->status = 'Cicilan';
        }

        $bill->save();

        DB::commit();

        return response()->json($payment->load('studentBill.student', 'studentBill.costItem'), 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Terjadi kesalahan saat memproses pembayaran.', 'error' => $e->getMessage()], 500);
    }
}

    /**
     * Generate dan unduh kwitansi PDF.
     * GET /api/payments/{payment}/receipt
     */
    public function generateReceipt(Payment $payment)
{
    // Tambahkan 'user' untuk mengambil data pemroses pembayaran
    $payment->load('studentBill.student', 'studentBill.costItem', 'user');
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
                Payment::create([
                    'student_bill_id' => $bill->id,
                    'payment_date' => now(),
                    'amount_paid' => $bill->remaining_amount,
                    'receipt_number' => $nextNumber, // Gunakan nomor baru
                    'user_id' => auth()->id(),
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
public function storeMultiBill(Request $request)
{
    $validated = $request->validate([
        'student_bill_ids' => 'required|array|min:1',
        'student_bill_ids.*' => 'exists:student_bills,id',
        'receipt_number' => 'required|string', // Validasi unique dihapus karena sekarang boleh sama
    ]);

    DB::beginTransaction();
    try {
        $bills = StudentBill::whereIn('id', $validated['student_bill_ids'])->get();

        if ($bills->pluck('student_id')->unique()->count() > 1) {
            return response()->json(['message' => 'Semua tagihan harus milik siswa yang sama.'], 422);
        }

        // Loop melalui setiap tagihan
        foreach ($bills as $bill) {
            if ($bill->status !== 'Lunas') {
                Payment::create([
                    'student_bill_id' => $bill->id,
                    'payment_date' => now(),
                    'amount_paid' => $bill->remaining_amount,
                    // GUNAKAN NOMOR KWITANSI YANG SAMA UNTUK SEMUA
                    'receipt_number' => $validated['receipt_number'],
                    'payment_method' => 'Tunai',
                    'user_id' => auth()->id(),
                    'reconciled_at' => now(),
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
    $payments = Payment::with('studentBill.student', 'studentBill.costItem', 'user')
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

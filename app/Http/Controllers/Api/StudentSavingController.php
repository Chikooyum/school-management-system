<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Payment;
use App\Models\StudentBill;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;// <-- Tambahkan
use Illuminate\Http\Request;

class StudentSavingController extends Controller
{
    // Fungsi helper untuk memeriksa otorisasi guru
    private function authorizeTeacher(Student $student)
    {
        $user = auth()->user();
        if ($user->role === 'guru') {
            $staff = Staff::where('user_id', $user->id)->first();
            if (!$staff || !$staff->classGroups()->whereHas('students', fn($q) => $q->where('id', $student->id))->exists()) {
                abort(403, 'Akses ditolak.');
            }
        }
    }

    public function index(Student $student)
    {
        $this->authorizeTeacher($student); // Cek otorisasi

        $transactions = $student->savings()->latest('transaction_date')->get();
        $balance = $transactions->where('type', 'Setoran')->sum('amount') - $transactions->where('type', 'Penarikan')->sum('amount');

        return response()->json([
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $this->authorizeTeacher($student); // Cek otorisasi

        $data = $request->validate([
            'type' => 'required|in:Setoran,Penarikan',
            'amount' => 'required|numeric|gt:0',
            'description' => 'nullable|string|max:255',
        ]);

        if ($data['type'] === 'Penarikan') {
            $transactions = $student->savings()->get();
            $balance = $transactions->where('type', 'Setoran')->sum('amount') - $transactions->where('type', 'Penarikan')->sum('amount');
            if ($data['amount'] > $balance) {
                return response()->json(['message' => 'Saldo tidak mencukupi.'], 422);
            }
        }

        $actor = auth()->user();
    $student->load('classGroup.waliKelas.user'); // Muat relasi yang dibutuhkan
    $waliKelasUser = $student->classGroup?->waliKelas?->user;
    $handoverUserId = ($actor->role === 'sysadmin' && $waliKelasUser) ? $waliKelasUser->id : $actor->id;

    $transaction = $student->savings()->create([
        'transaction_date' => now(),
        'type' => $data['type'],
        'amount' => $data['amount'],
        'description' => $data['description'],
        'processed_by_user_id' => $actor->id,
        'handover_user_id' => $handoverUserId,
    ]);
        return response()->json($transaction, 201);
    }
    // app/Http/Controllers/Api/StudentSavingController.php

public function withdrawAndPay(Request $request, Student $student)
{
    $data = $request->validate([
        'student_bill_id' => 'required|exists:student_bills,id',
        'receipt_number' => 'required|string|unique:payments,receipt_number',
    ]);

    $bill = StudentBill::findOrFail($data['student_bill_id']);

    if ($bill->student_id !== $student->id) {
        abort(403, 'Tagihan tidak sesuai dengan siswa.');
    }

    $amountToPay = $bill->remaining_amount;

    // Validasi saldo
    $currentBalance = $student->savings()->where('type', 'Setoran')->sum('amount') - $student->savings()->where('type', 'Penarikan')->sum('amount');
    if ($amountToPay > $currentBalance) {
        return response()->json(['message' => 'Saldo tabungan tidak mencukupi untuk membayar tagihan ini.'], 422);
    }

    DB::transaction(function () use ($student, $bill, $amountToPay, $data) {
        $actor = auth()->user();

        // 1. Catat sebagai penarikan dari tabungan (dengan kolom yang benar)
        $student->savings()->create([
            'transaction_date' => now(),
            'type' => 'Penarikan',
            'amount' => $amountToPay,
            'description' => 'Pembayaran tagihan: ' . $bill->costItem->name,
            'processed_by_user_id' => $actor->id,
            'handover_user_id' => $actor->id, // Transaksi internal, tanggung jawabnya sama dengan pemroses
            'reconciled_at' => now(),
        ]);

        // 2. Catat sebagai pembayaran tagihan (dengan kolom yang benar)
        Payment::create([
            'student_bill_id' => $bill->id,
            'payment_date' => now(),
            'amount_paid' => $amountToPay,
            'payment_method' => 'Tabungan',
            'receipt_number' => $data['receipt_number'],
            'processed_by_user_id' => $actor->id,
            'handover_user_id' => $actor->id,
            'reconciled_at' => now(),
        ]);

        // 3. Update status tagihan
        $bill->update([
            'remaining_amount' => 0,
            'status' => 'Lunas',
        ]);
    });

    return response()->json(['message' => 'Tagihan berhasil dibayar menggunakan saldo tabungan.']);
}

}

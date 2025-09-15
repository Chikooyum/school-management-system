<!DOCTYPE html>
<html>
<head>
    <title>Kwitansi Pembayaran</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 20px; font-size: 14px; }
        .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; }
        .details, .payment-info { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
        .details td, .payment-info td { padding: 8px; }
        .text-right { text-align: right; }
        .total { font-weight: bold; font-size: 16px; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; }
        hr { border: 0; border-top: 1px solid #ccc; }

        /* --- STYLE BARU UNTUK WATERMARK --- */
        .watermark-container {
            position: relative; /* Diperlukan agar watermark bisa diposisikan */
        }
        .watermark-container::after {
            content: "";
            background-image: url('{{ public_path('images/logo.png') }}'); /* Ganti 'logo.png' jika nama file Anda berbeda */
            background-size: 80%; /* Ukuran watermark, bisa Anda sesuaikan */
            background-position: center;
            background-repeat: no-repeat;
            position: absolute;
            top: 25%; /* Posisi vertikal, sesuaikan jika perlu */
            left: 0;
            width: 100%;
            height: 50%; /* Tinggi area watermark, sesuaikan jika perlu */
            opacity: 0.1; /* Tingkat transparansi, 0.1 = 10% */
            z-index: -1; /* Letakkan di belakang konten */
        }
        /* --- AKHIR STYLE BARU --- */
    </style>
</head>
<body>
    <div class="watermark-container">
        <div class="container">
            <div class="header">
    <table style="width: 100%; border: none; text-align: center;">
        <tr>
            <td style="width: 15%; text-align: left;">
                <img src="{{ public_path('images/logo.png') }}" alt="Logo Sekolah" style="width: 80px; height: auto;">
            </td>

            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 16px; font-weight: bold;">{{ config('school.foundation') }}</h3>
                <h2 style="margin: 5px 0; font-size: 20px; font-weight: bold;">{{ config('school.name') }}</h2>
                <p style="margin: 5px 0; font-size: 12px;">{{ config('school.details') }}</p>
                <p style="margin: 5px 0; font-size: 12px;">{{ config('school.address_1') }}</p>
                <p style="margin: 5px 0; font-size: 12px;">{{ config('school.address_2') }}</p>
            </td>
        </tr>
    </table>
    <hr style="border-top: 2px solid black; margin-top: 10px;">
    <h2 style="margin-top: 10px;">KWITANSI PEMBAYARAN</h2>
</div>

            @php
    // Support untuk single payment dan multiple payments
    $firstPayment = isset($payments) ? $payments->first() : $payment;
    $allPayments = isset($payments) ? $payments : collect([$payment]);
    $totalAmount = $allPayments->sum('amount_paid');
@endphp

<table class="details">
    <tr>
        <td><strong>No. Kwitansi:</strong> {{ $firstPayment->receipt_number }}</td>
        <td class="text-right"><strong>Tanggal Bayar:</strong> {{ \Carbon\Carbon::parse($firstPayment->payment_date)->setTimezone('Asia/Jakarta')->format('d F Y') }}</td>
    </tr>
    <tr>
        <td colspan="2"><strong>Diterima dari:</strong> {{ $firstPayment->studentBill->student->name }}</td>
    </tr>
     <tr>
    <td colspan="2"><strong>Diproses oleh:</strong> {{ $firstPayment->processor->name }}</td>
</tr>
</table>

            <table class="payment-info">
    <tr style="background-color: #f2f2f2;">
        <td><strong>Deskripsi Pembayaran</strong></td>
        <td class="text-right"><strong>Jumlah</strong></td>
    </tr>
    @foreach ($allPayments as $paymentItem)
        <tr>
            <td>{{ $paymentItem->studentBill->costItem->name }}</td>
            <td class="text-right">Rp {{ number_format($paymentItem->amount_paid, 0, ',', '.') }}</td>
        </tr>
        @if ($paymentItem->payment_method === 'Tabungan')
            <tr><td colspan="2" style="font-size:10px; font-style:italic; padding-top:0;">(dipotong dari saldo tabungan)</td></tr>
        @endif
    @endforeach
    <tr style="border-top: 1px solid #ccc;">
        <td class="text-right total">TOTAL</td>
        <td class="text-right total">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
    </tr>
</table>

            @if ($allPayments->where('payment_method', 'Tabungan')->count() > 0)
    <div class="notes">
        <p><strong>Catatan:</strong>
        @if ($allPayments->where('payment_method', 'Tabungan')->count() === $allPayments->count())
            Semua pembayaran dipotong dari saldo tabungan siswa.
        @else
            Sebagian pembayaran dipotong dari saldo tabungan siswa.
        @endif
        </p>
    </div>
@endif

            <div class="footer">
                <p>Terima kasih atas pembayaran Anda.</p>
                <p>Kwitansi ini dicetak oleh sistem dan sah tanpa tanda tangan.</p>
            </div>
        </div>
    </div>
</body>
</html>

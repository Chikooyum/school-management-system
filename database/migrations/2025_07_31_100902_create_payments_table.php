<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_payments_table.php
public function up(): void
{
    Schema::create('payments', function (Blueprint $table) {
        $table->id(); // Ini adalah UNSIGNED BIGINT

        // foreignId() akan membuat kolom UNSIGNED BIGINT yang cocok
        $table->foreignId('student_bill_id')->constrained('student_bills')->onDelete('cascade');

        $table->date('payment_date');
        $table->decimal('amount_paid', 15, 2);
        $table->string('receipt_number')->unique();

        // foreignId() juga cocok dengan kolom id di tabel users
        $table->foreignId('user_id')->comment('Sysadmin yg memproses')->constrained('users');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('payments', function (Blueprint $table) {
        // Default 'Tunai' untuk semua pembayaran yang sudah ada
        $table->string('payment_method')->default('Tunai')->after('amount_paid');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }
};

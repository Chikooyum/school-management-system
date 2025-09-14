<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_student_bills_table.php
public function up(): void
{
    Schema::create('student_bills', function (Blueprint $table) {
        $table->id(); // <-- PENTING: Membuat id sebagai UNSIGNED BIGINT

        // foreignId() akan membuat kolom yang cocok dengan id()
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        $table->foreignId('cost_item_id')->constrained('cost_items')->onDelete('cascade');

        $table->date('due_date')->nullable();
        $table->decimal('remaining_amount', 15, 2);
        $table->enum('status', ['Belum Lunas', 'Cicilan', 'Lunas'])->default('Belum Lunas');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_bills');
    }
};

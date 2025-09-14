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
    Schema::table('student_savings', function (Blueprint $table) {
        // Tambahkan kolom user_id yang terhubung ke tabel users
        $table->foreignId('user_id')->nullable()->after('student_id')->constrained('users')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_savings', function (Blueprint $table) {
            //
        });
    }
};

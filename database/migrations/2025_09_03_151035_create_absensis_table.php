<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_absensis_table.php
public function up()
{
    Schema::create('absensis', function (Blueprint $table) {
        $table->id();
        $table->foreignId('teacher_id')->constrained('users');
        $table->foreignId('student_id')->constrained('students');
        $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha'])->default('hadir');
        $table->date('tanggal');
        $table->text('keterangan')->nullable();
        $table->timestamps();

        $table->unique(['teacher_id', 'student_id', 'tanggal']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};

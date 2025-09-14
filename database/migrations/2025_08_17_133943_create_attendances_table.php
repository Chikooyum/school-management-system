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
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        $table->foreignId('class_group_id')->constrained('class_groups')->onDelete('cascade');
        $table->date('attendance_date');
        $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alpa']);
        $table->string('notes')->nullable();
        $table->foreignId('recorded_by')->constrained('users'); // Siapa yang mencatat
        $table->timestamps();

        // Mencegah data ganda untuk siswa yang sama di hari yang sama
        $table->unique(['student_id', 'attendance_date']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

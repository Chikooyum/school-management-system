<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_students_table.php
public function up(): void
{
    Schema::create('students', function (Blueprint $table) {
        $table->id(); // <-- PENTING: Membuat id sebagai UNSIGNED BIGINT
        $table->string('name');
        $table->string('nis')->unique()->nullable();
        $table->year('enrollment_year');
        $table->tinyInteger('registration_wave');
        $table->date('date_of_birth');
        $table->string('father_name')->nullable();
        $table->string('mother_name');
        $table->date('mother_date_of_birth');
        $table->text('address')->nullable();
        $table->string('phone_number')->nullable();
        $table->enum('status', ['Aktif', 'Alumni', 'Cuti'])->default('Aktif');
        $table->boolean('is_alumni_sibling')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

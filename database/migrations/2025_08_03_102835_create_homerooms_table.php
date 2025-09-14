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
    Schema::create('homerooms', function (Blueprint $table) {
        $table->id();
        $table->year('enrollment_year')->unique(); // Angkatan tahun berapa
        $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade'); // Siapa wali kelasnya
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homerooms');
    }
};

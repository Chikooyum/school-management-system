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
    Schema::create('downloadable_files', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('filename'); // Nama file asli
        $table->string('path');     // Path penyimpanan di server
        $table->foreignId('user_id')->constrained('users');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloadable_files');
    }
};

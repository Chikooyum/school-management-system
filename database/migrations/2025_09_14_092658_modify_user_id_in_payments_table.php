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
        // Ganti nama kolom user_id menjadi processed_by_user_id
        $table->renameColumn('user_id', 'processed_by_user_id');
        // Tambah kolom baru untuk menandai siapa yang harus setor
        $table->foreignId('handover_user_id')->nullable()->after('processed_by_user_id')->constrained('users')->onDelete('set null');
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

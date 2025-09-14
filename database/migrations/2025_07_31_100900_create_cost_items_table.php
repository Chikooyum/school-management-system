<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_cost_items_table.php
public function up(): void
{
    Schema::create('cost_items', function (Blueprint $table) {
        $table->id(); // <-- PENTING: Membuat id sebagai UNSIGNED BIGINT
        $table->string('name');
        $table->string('cost_code')->unique()->nullable();
        $table->enum('type', ['Tetap', 'Dinamis']);
        $table->decimal('amount', 15, 2);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_items');
    }
};

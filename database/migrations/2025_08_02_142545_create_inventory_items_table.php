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
    Schema::create('inventory_items', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('item_code')->unique();
        $table->string('category');
        $table->string('location');
        $table->date('purchase_date')->nullable();
        $table->decimal('price', 15, 2)->default(0);
        $table->string('photo_path')->nullable();
        $table->enum('status', ['Baik', 'Rusak', 'Perlu Perbaikan'])->default('Baik');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};

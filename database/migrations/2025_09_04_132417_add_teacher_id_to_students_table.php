<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('students', function (Blueprint $table) {
        $table->foreignId('teacher_id')->nullable()->constrained('users');
    });
}

public function down()
{
    Schema::table('students', function (Blueprint $table) {
        $table->dropForeign(['teacher_id']);
        $table->dropColumn('teacher_id');
    });
}
};

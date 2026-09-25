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
        Schema::table('reports', function (Blueprint $table) {
            // Варіант 1: Через ENUM (суворі обмеження)
            $table->enum('type', ['import', 'export'])->default('import')->after('id');

            // Варіант 2: Через звичайний STRING (якщо в майбутньому типів стане більше)
            // $table->string('type')->default('import')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

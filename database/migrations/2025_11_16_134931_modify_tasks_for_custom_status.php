<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // 1. Tambahkan kolom status_id baru
            // Kita buat 'nullable' dan 'after' description agar rapi
            $table->foreignId('status_id')
                  ->nullable()
                  ->after('description')
                  ->constrained('statuses') // Terhubung ke tabel 'statuses'
                  ->onDelete('set null'); // Jika status dihapus, task tidak ikut terhapus

            // 2. Hapus kolom 'status' string yang lama
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // 1. Kembalikan kolom 'status' string yang lama
            $table->string('status')->default('pending')->after('description');

            // 2. Hapus foreign key dan kolom 'status_id'
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};

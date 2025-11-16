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
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            
            // Nama status (misal: "On Progress", "Done")
            $table->string('name');
            
            // Kode warna (misal: "#FF0000" atau "bg-red-500")
            $table->string('color');

            // --- Pemilik Status ---
            // Jika ini status personal
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Jika ini status tim
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};

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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // Misal: 'pending', 'in_progress', 'completed'
            $table->datetime('deadline')->nullable();

            // === KONTEKS TUGAS (Personal atau Tim?) ===
            // Jika personal, user_id diisi, team_id NULL
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Jika tim, team_id diisi, user_id NULL
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');

            // === PENANGGUNG JAWAB ===
            // Siapa yang membuat tugas ini? (Selalu diisi)
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            
            // Siapa yang ditugaskan? (Untuk tugas tim)
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

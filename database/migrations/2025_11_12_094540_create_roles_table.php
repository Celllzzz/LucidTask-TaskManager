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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            
            // Kolom ini mengikat Role ke Tim
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            
            $table->string('name'); // 'Manager', 'Developer', 'Guest'
            $table->text('description')->nullable();
            
            // Memastikan sebuah tim tidak bisa punya 2 role dengan nama yang sama
            // Tapi Tim A dan Tim B BOLEH sama-sama punya role 'Manager'
            $table->unique(['team_id', 'name']); 
            
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

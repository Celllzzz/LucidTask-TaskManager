<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\Schema; 

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // (1) Matikan foreign key check
        Schema::disableForeignKeyConstraints(); 

        // (2) Kosongkan tabel
        Permission::truncate(); 

        // (3) Nyalakan kembali foreign key check
        Schema::enableForeignKeyConstraints(); 

        $permissions = [
            // Manajemen Tim & Anggota
            ['slug' => 'invite_member', 'description' => 'Mengundang anggota baru ke tim'],
            ['slug' => 'remove_member', 'description' => 'Mengeluarkan anggota dari tim'],
            ['slug' => 'manage_roles', 'description' => 'Membuat, mengedit, dan menghapus role'],
            
            // Manajemen Tugas (Tim)
            ['slug' => 'create_task', 'description' => 'Membuat tugas baru untuk tim'],
            ['slug' => 'edit_task', 'description' => 'Mengedit semua tugas di tim'],
            ['slug' => 'delete_task', 'description' => 'Menghapus semua tugas di tim'],
            ['slug' => 'assign_task', 'description' => 'Menugaskan tugas ke anggota'],
            ['slug' => 'comment_on_task', 'description' => 'Memberi komentar pada tugas'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
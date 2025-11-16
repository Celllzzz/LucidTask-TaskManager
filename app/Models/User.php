<?php

namespace App\Models;

use App\Models\Team;
use App\Models\Task;
use App\Models\Comment;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Secara eksplisit memberi tahu model ini untuk menggunakan tabel 'users'.
     * Ini adalah perbaikan untuk error Anda.
     */
    protected $table = 'users'; // <-- 2. INI AKAN MEMPERBAIKI ERROR SQL ANDA

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //=================================================================
    // RELASI ELOQUENT
    //=================================================================

    /**
     * Mendefinisikan tim yang dimiliki (dibuat) oleh user ini.
     */
    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    /**
     * Mendefinisikan semua tim di mana user ini terdaftar sebagai anggota.
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user')
                    ->withPivot('role_id') // Penting: agar kita bisa cek role-nya
                    ->withTimestamps(); // Jika tabel pivot Anda punya timestamps
    }

    /**
     * Mendefinisikan tugas-tugas personal yang dimiliki user ini.
     * (Tugas di mana user_id diisi)
     */
    public function personalTasks()
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    /**
     * Mendefinisikan semua tugas yang 'dibuat' oleh user ini.
     * (Baik personal maupun di dalam tim)
     */
    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by_user_id');
    }

    /**
     * Mendefinisikan semua tugas yang 'ditugaskan' kepada user ini.
     * (Hanya relevan untuk tugas tim)
     */
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to_user_id');
    }

    /**
     * Mendefinisikan semua komentar yang pernah ditulis oleh user ini.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'user_id');
    }

    /**
     * Mendefinisikan semua status kustom personal milik user ini.
     */
    public function personalStatuses()
    {
        return $this->hasMany(Status::class, 'user_id');
    }
}
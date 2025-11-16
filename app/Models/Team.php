<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Role;
use App\Models\Task;

class Team extends Model
{
    //

    /**
     * Mendefinisikan user yang 'memiliki' (membuat) tim ini.
     * Relasi kebalikan dari User->ownedTeams()
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Mendefinisikan semua user yang menjadi 'anggota' tim ini.
     * Relasi kebalikan dari User->teams()
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user')
                    ->withPivot('role_id') // Penting: agar kita bisa lihat role setiap anggota
                    ->withTimestamps();
    }

    /**
     * Mendefinisikan semua role kustom yang dimiliki oleh tim ini.
     */
    public function roles()
    {
        return $this->hasMany(Role::class, 'team_id');
    }

    /**
     * Mendefinisikan semua tugas yang dimiliki oleh tim ini.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'team_id');
    }
}

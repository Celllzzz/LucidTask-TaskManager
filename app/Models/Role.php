<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Team;
use App\Models\Permission;

class Role extends Model
{
    //

    /**
     * Mendefinisikan tim yang memiliki role ini.
     * Relasi kebalikan dari Team->roles()
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Mendefinisikan semua permission (hak akses) yang dimiliki oleh role ini.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }
}

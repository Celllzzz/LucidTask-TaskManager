<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Role;

class Permission extends Model
{
    //

    /**
     * Mendefinisikan semua role yang memiliki permission (hak akses) ini.
     * Relasi kebalikan dari Role->permissions()
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}

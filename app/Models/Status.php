<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    /**
     * Tentukan kolom mana yang boleh diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'color',
        'user_id', 
        'team_id', 
    ];

    /**
     * Mendefinisikan user (pemilik) dari status personal ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mendefinisikan tim (pemilik) dari status tim ini.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Mendefinisikan semua task yang menggunakan status ini.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'status_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Team;
use App\Models\Comment;
use App\Models\Status; 

class Task extends Model
{
    use HasFactory;

    /**
     * PERBAIKAN: Tentukan kolom mana yang boleh diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'notes',
        'status_id', 
        'deadline',
        'user_id',
        'team_id',
        'created_by_user_id',
        'assigned_to_user_id',
    ];

    //=================================================================
    // RELASI ELOQUENT
    //=================================================================
    
    /**
     * 4. TAMBAHKAN RELASI BARU KE STATUS
     * Mendefinisikan status yang dimiliki oleh tugas ini.
     */
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Mendefinisikan 'konteks' user jika ini adalah tugas personal.
     */
    public function personalUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ... (sisa relasi 'team', 'creator', 'assignee', 'comments' tetap sama) ...
    // (pastikan relasi di bawah ini masih ada di file Anda)

    /**
     * Mendefinisikan 'konteks' tim jika ini adalah tugas tim.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Mendefinisikan user yang 'membuat' tugas ini.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Mendefinisikan user yang 'ditugaskan' untuk tugas ini.
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Mendefinisikan semua komentar yang ada di tugas ini.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id');
    }
}
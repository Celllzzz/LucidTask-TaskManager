<?php

namespace App\Models;

use IlluminateS\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Task;
use App\Models\User;

class Comment extends Model
{
    //

    /**
     * Mendefinisikan task tempat komentar ini berada.
     * Relasi kebalikan dari Task->comments()
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Mendefinisikan user yang menulis komentar ini.
     * Relasi kebalikan dari User->comments()
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

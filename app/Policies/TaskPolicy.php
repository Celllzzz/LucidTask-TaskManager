<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Tentukan apakah user dapat mengupdate task.
     */
    public function update(User $user, Task $task): bool
    {
        // Izinkan HANYA jika ID user sama dengan ID 'user_id' di task
        // (Ini untuk tugas personal)
        return $user->id === $task->user_id;
    }

    /**
     * Tentukan apakah user dapat menghapus task.
     */
    public function delete(User $user, Task $task): bool
    {
        // Izinkan HANYA jika ID user sama dengan ID 'user_id' di task
        // (Ini untuk tugas personal)
        return $user->id === $task->user_id;
    }
}
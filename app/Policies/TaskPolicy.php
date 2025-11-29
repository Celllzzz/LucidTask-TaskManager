<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Tentukan siapa yang boleh meng-update tugas.
     */
    public function update(User $user, Task $task): bool
    {
        // KASUS 1: Tugas Tim
        if ($task->team_id) {
            // Boleh edit jika: User adalah Pemilik Tim ATAU User adalah orang yang ditugaskan (Assignee)
            return $user->id === $task->team->owner_id || $user->id === $task->assigned_to_user_id;
        }

        // KASUS 2: Tugas Personal
        return $user->id === $task->user_id;
    }

    /**
     * Tentukan siapa yang boleh menghapus tugas.
     */
    public function delete(User $user, Task $task): bool
    {
        // KASUS 1: Tugas Tim
        if ($task->team_id) {
            // HANYA Pemilik Tim (Leader) yang boleh menghapus tugas
            return $user->id === $task->team->owner_id;
        }

        // KASUS 2: Tugas Personal
        return $user->id === $task->user_id;
    }
}
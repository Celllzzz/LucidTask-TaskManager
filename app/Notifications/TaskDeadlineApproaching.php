<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Task;

class TaskDeadlineApproaching extends Notification
{
    use Queueable;

    public $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Simpan ke database
    }

    public function toArray(object $notifiable): array
    {
        // Tentukan teks berdasarkan tipe tugas
        $context = $this->task->team_id ? "Team: {$this->task->team->name}" : "Personal";

        return [
            'task_id' => $this->task->id,
            'message' => "Deadline Besok: {$this->task->name} ({$context})",
            // Jika tugas tim, arahkan ke dashboard tim. Jika personal, ke dashboard utama.
            'url' => $this->task->team_id 
                ? route('teams.show', $this->task->team_id) 
                : route('dashboard'),
        ];
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Notifications\TaskDeadlineApproaching;
use Carbon\Carbon;

class CheckTaskDeadlines extends Command
{
    /**
     * Nama perintah yang akan dijalankan di terminal.
     */
    protected $signature = 'app:check-deadlines';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Memeriksa tugas yang deadline-nya besok dan mengirim notifikasi';

    /**
     * Eksekusi perintah.
     */
    public function handle()
    {
        $this->info('Memeriksa deadline tugas...');

        // 1. Tentukan rentang waktu "Besok"
        // Kita cek deadline antara besok jam 00:00 sampai 23:59
        $tomorrow = Carbon::tomorrow(); 
        
        // 2. Query Tugas
        // Kriteria:
        // - Deadline adalah besok
        // - Status tugas BUKAN 'Done' atau 'Completed' (Kita cek nama statusnya)
        $tasks = Task::whereDate('deadline', $tomorrow)
            ->whereHas('status', function ($query) {
                $query->whereNotIn('name', ['Done', 'Completed', 'Selesai']); 
            })
            ->with(['assignee', 'personalUser']) // Eager load user
            ->get();

        $count = 0;

        foreach ($tasks as $task) {
            // 3. Tentukan Penerima Notifikasi
            $recipient = null;

            if ($task->team_id) {
                // Jika Tugas Tim: Kirim ke Assignee (jika ada)
                $recipient = $task->assignee;
            } else {
                // Jika Tugas Personal: Kirim ke Pemilik
                $recipient = $task->personalUser;
            }

            // 4. Kirim Notifikasi (jika penerima valid)
            if ($recipient) {
                $recipient->notify(new TaskDeadlineApproaching($task));
                $this->info("Notifikasi dikirim untuk tugas: {$task->name} ke user: {$recipient->name}");
                $count++;
            }
        }

        $this->info("Selesai. {$count} notifikasi dikirim.");
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Task;

class TaskController extends Controller
{
    /**
     * Menyimpan tugas personal baru ke database.
     */
    /**
     * Menyimpan tugas personal baru ke database.
     */
    public function storePersonalTask(Request $request)
    {
        // 1. Validasi data (dengan validasi status_id baru)
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status_id' => [ // <-- TAMBAHKAN VALIDASI INI
                'nullable',
                'integer',
                // Pastikan status_id yang dikirim adalah milik user yang login
                Rule::exists('statuses', 'id')->where(function ($query) {
                    $query->where('user_id', Auth::id());
                })
            ],
        ]);

        // 2. Buat tugas baru
        Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'status_id' => $request->status_id, // <-- TAMBAHKAN BARIS INI
            
            // === Kunci untuk Tugas Personal ===
            'user_id' => Auth::id(), 
            'created_by_user_id' => Auth::id(), 
        ]);

        // 3. Kembalikan ke halaman dashboard
        return redirect()->route('dashboard')->with('success', 'Task added successfully!');
    }

    /**
     * Menampilkan halaman form untuk membuat tugas personal baru.
     */
    public function createPersonalTask()
    {
        // 1. Ambil semua status personal milik user
        $statuses = Auth::user()->personalStatuses()->get();
        
        // 2. Kirim data 'statuses' ke view
        return view('tasks.create', [
            'statuses' => $statuses
        ]);
    }

    /**
     * Menghapus tugas personal dari database.
     */
    public function destroyPersonalTask(Task $task)
    {
        // 1. Otorisasi (Cek Keamanan)
        // Ini akan memanggil TaskPolicy Anda secara otomatis.
        // Jika user bukan pemilik, ini akan error (Forbidden).
        $this->authorize('delete', $task);

        // 2. Hapus Tugas
        $task->delete();

        // 3. Kembalikan ke dashboard
        return redirect()->route('dashboard')->with('success', 'Task deleted successfully!');
    }

    /**
     * Mengupdate tugas personal di database.
     */
/**
     * Mengupdate tugas personal di database.
     * Method ini sekarang mendukung request JSON (untuk inline edit) 
     * dan hanya mengupdate field yang dikirim.
     */
    public function updatePersonalTask(Request $request, Task $task)
    {
        // 1. Otorisasi (Cek Keamanan - SANGAT PENTING)
        $this->authorize('update', $task);

        // 2. Validasi data (hanya validasi field yang ada di request)
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'deadline' => 'sometimes|nullable|date',
            'status_id' => [
                'sometimes', // 'sometimes' berarti hanya validasi jika field ini dikirim
                'nullable',
                'integer',
                Rule::exists('statuses', 'id')->where(function ($query) {
                    $query->where('user_id', Auth::id());
                })
            ],
        ]);

        // 3. Update data di database (hanya field yang tervalidasi)
        $task->update($validatedData);

        // 4. Kembalikan respons JSON
        // Kita 'load('status')' untuk memastikan data relasi (warna/nama status)
        // juga ikut terkirim kembali ke frontend.
        return response()->json($task->load('status'));
    }
}

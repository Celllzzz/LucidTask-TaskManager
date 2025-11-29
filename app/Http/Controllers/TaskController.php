<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Task;
use App\Models\Team;

class TaskController extends Controller
{
    use AuthorizesRequests;
    /**
     * Menyimpan tugas personal baru ke database.
     */
    public function storePersonalTask(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status_id' => [ 
                'nullable',
                'integer',
                // Pastikan status_id yang dikirim adalah milik user yang login
                Rule::exists('statuses', 'id')->where(function ($query) {
                    $query->where('user_id', Auth::id());
                })
            ],
        ]);

        Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'status_id' => $request->status_id, 
            
            // === Kunci untuk Tugas Personal ===
            'user_id' => Auth::id(), 
            'created_by_user_id' => Auth::id(), 
        ]);

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
     * Menghapus tugas personal.
     */
    public function destroyPersonalTask(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();
        return response()->json(['message' => 'Task deleted successfully']);
    }

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

    /**
     * Menyimpan tugas TIM baru.
     */
    public function storeTeamTask(Request $request, Team $team)
    {
        // 1. Cek Otorisasi: Pastikan user adalah anggota tim
        if (!$team->members->contains(Auth::id())) {
            abort(403, 'Anda bukan anggota tim ini.');
        }

        // 2. Validasi
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'assigned_to_user_id' => [
                'nullable', 
                'exists:users,id',
                // Validasi tambahan: User yang di-assign HARUS anggota tim ini
                function ($attribute, $value, $fail) use ($team) {
                    if ($value && !$team->members->contains($value)) {
                        $fail('User yang ditugaskan harus anggota tim ini.');
                    }
                },
            ],
        ]);

        // 3. Buat Tugas Tim
        Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'status_id' => null, // Nanti kita buat status kustom untuk tim juga
            
            // KUNCI: Set konteks Tim
            'team_id' => $team->id,
            'user_id' => null, // Ini bukan tugas personal
            
            'created_by_user_id' => Auth::id(),
            'assigned_to_user_id' => $request->assigned_to_user_id,
        ]);

        return back()->with('success', 'Team task created successfully!');
    }

    /**
     * Mengupdate tugas TIM.
     */
    public function updateTeamTask(Request $request, Task $task)
    {
        // 1. Cek Policy (Otorisasi RBAC)
        $this->authorize('update', $task);

        // 2. Validasi
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'deadline' => 'sometimes|nullable|date',
            'assigned_to_user_id' => 'sometimes|nullable|exists:users,id',
            'status_id' => 'sometimes|required|exists:statuses,id', 
        ]);

        // 3. Update Task
        $task->update($validatedData);

        // 4. Return JSON
        // PENTING: Load 'status' agar frontend menerima warna status baru
        return response()->json($task->load(['assignee', 'status']));
    }

    /**
     * Menghapus tugas TIM.
     */
    public function destroyTeamTask(Task $task)
    {
        // 1. Cek Policy (Hanya Leader yang lolos)
        $this->authorize('delete', $task);

        // 2. Hapus
        $task->delete();

        // 3. Return JSON
        return response()->json(['message' => 'Team task deleted successfully']);
    }
}

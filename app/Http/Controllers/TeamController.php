<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use App\Models\Team;
use App\Models\Role;
use App\Models\User; 

class TeamController extends Controller
{
    /**
     * Menampilkan halaman form untuk membuat tim baru.
     */
    public function create()
    {
        return view('teams.create');
    }

    /**
     * Menyimpan tim baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Gunakan DB Transaction agar aman (data konsisten)
        // Kita ingin create Team DAN create Role DAN attach User sekaligus.
        DB::transaction(function () use ($request) {
            $user = Auth::user();

            // 1. Buat Tim
            $team = Team::create([
                'name' => $request->name,
                'owner_id' => $user->id,
            ]);

            // 2. Buat Role Default untuk Tim ini ('Leader' & 'Member')
            $leaderRole = Role::create([
                'team_id' => $team->id,
                'name' => 'Leader',
                'description' => 'Pemilik tim dengan akses penuh'
            ]);

            Role::create([
                'team_id' => $team->id,
                'name' => 'Member',
                'description' => 'Anggota tim biasa'
            ]);

            // 3. Buat default status untuk team
            $statuses = [
                ['name' => 'To Do', 'color' => '#6b7280'],       // Abu-abu
                ['name' => 'In Progress', 'color' => '#3b82f6'], // Biru
                ['name' => 'Done', 'color' => '#10b981'],        // Hijau
            ];

            foreach ($statuses as $status) {
                \App\Models\Status::create([
                    'team_id' => $team->id,
                    'name' => $status['name'],
                    'color' => $status['color'],
                ]);
            }

            // 4. Masukkan User pembuat ke dalam tim sebagai 'Leader'
            $team->members()->attach($user->id, ['role_id' => $leaderRole->id]);
        });

        return redirect()->route('dashboard')->with('success', 'Team created successfully!');
    }

    /**
     * Tampilkan Dashboard Tim.
     */
    public function show(Team $team)
    {
        $user = Auth::user();

        if (!$user->teams->contains($team)) {
            abort(403);
        }

        // 1. Ambil Permissions untuk Form Role (Fitur sebelumnya)
        $availablePermissions = \App\Models\Permission::all();

        // 2. LOGIKA VISIBILITAS TUGAS
        $query = $team->tasks()->with(['assignee', 'status']); // Eager load

        if ($team->owner_id !== $user->id) {
            // Jika BUKAN Leader, hanya tampilkan tugas milik sendiri
            $query->where('assigned_to_user_id', $user->id);
        }
        
        $teamTasks = $query->latest()->get();

        // 3. Ambil Status Tim (Untuk Dropdown)
        $teamStatuses = \App\Models\Status::where('team_id', $team->id)->get();

        // Kirim data ke view
        return view('teams.show', compact('team', 'availablePermissions', 'teamTasks', 'teamStatuses'));
    }

    /**
     * Menghapus tim.
     */
    public function destroy(Team $team)
    {
        // Cek Otorisasi: Hanya pemilik (owner_id) yang boleh menghapus
        if (Auth::id() !== $team->owner_id) {
            abort(403, 'Hanya pemilik tim yang dapat menghapus tim ini.');
        }

        $team->delete();

        return redirect()->route('dashboard')->with('success', 'Team deleted successfully!');
    }

    /**
     * Mengundang anggota baru ke tim.
     */
    public function storeMember(Request $request, Team $team)
    {
        // 1. Validasi Input
        $request->validate([
            'email' => 'required|email|exists:users,email', // Email harus ada di tabel users
        ]);

        // 2. Cek Otorisasi (Hanya Owner yang boleh invite)
        if (Auth::id() !== $team->owner_id) {
            abort(403, 'Hanya Leader yang dapat mengundang anggota.');
        }

        // 3. Cari User berdasarkan Email
        $userToInvite = User::where('email', $request->email)->first();

        // 4. Cek apakah user sudah menjadi anggota
        if ($team->members->contains($userToInvite->id)) {
            return back()->withErrors(['email' => 'User ini sudah menjadi anggota tim.']);
        }

        // 5. Cari Role 'Member' milik tim ini
        // (Kita asumsikan role 'Member' dibuat otomatis saat Create Team)
        $memberRole = Role::where('team_id', $team->id)
                          ->where('name', 'Member')
                          ->first();

        if (!$memberRole) {
             return back()->withErrors(['email' => 'Role default tidak ditemukan.']);
        }

        // 6. Attach User ke Tim via Pivot
        $team->members()->attach($userToInvite->id, ['role_id' => $memberRole->id]);

        return back()->with('success', 'Member added successfully!');
    }

    /**
     * Menghapus anggota dari tim.
     */
    public function removeMember(Team $team, User $user)
    {
        // 1. Cek Otorisasi: Hanya Leader yang boleh menghapus, 
        //    ATAU user menghapus dirinya sendiri (Leave Team).
        if (Auth::id() !== $team->owner_id && Auth::id() !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // 2. Jangan biarkan Leader menghapus dirinya sendiri (harus delete team sekalian)
        if ($user->id === $team->owner_id) {
            return back()->withErrors(['msg' => 'Pemilik tim tidak dapat dikeluarkan.']);
        }

        // 3. Hapus dari pivot table
        $team->members()->detach($user->id);

        return back()->with('success', 'Member removed successfully.');
    }
}
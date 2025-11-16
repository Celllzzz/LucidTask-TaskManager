<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Status;

class StatusController extends Controller
{
    /**
     * Mengambil semua status personal milik user yang sedang login.
     * Method ini akan dipanggil oleh popup modal (via JavaScript/AJAX).
     */
    public function getPersonalStatuses()
    {
        $statuses = Auth::user()->personalStatuses()->get();
        
        // Kita kembalikan sebagai JSON agar bisa dibaca oleh JavaScript
        return response()->json($statuses);
    }

    /**
     * Menyimpan status personal baru ke database.
     * Method ini akan dipanggil oleh popup modal (via JavaScript/AJAX).
     */
    public function storePersonalStatus(Request $request)
    {
        // 1. Validasi data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:20', // Misal: '#FF0000'
        ]);

        // 2. Buat status baru milik user yang sedang login
        $status = Auth::user()->personalStatuses()->create([
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        // 3. Kembalikan status yang baru dibuat sebagai JSON
        return response()->json($status, 201); // 201 = Created
    }

    // --- ⬇️ TAMBAHKAN DUA METHOD BARU DI BAWAH INI ⬇️ ---

    /**
     * Mengupdate status personal yang ada.
     */
    public function updatePersonalStatus(Request $request, Status $status)
    {
        // 1. Otorisasi (Keamanan): Pastikan user ini pemilik status
        if (Auth::id() !== $status->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 2. Validasi data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:20',
        ]);

        // 3. Update status
        $status->update($validated);

        // 4. Kembalikan status yang sudah di-update
        return response()->json($status);
    }

    /**
     * Menghapus status personal.
     */
    public function destroyPersonalStatus(Status $status)
    {
        // 1. Otorisasi (Keamanan): Pastikan user ini pemilik status
        if (Auth::id() !== $status->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 2. Hapus status
        $status->delete();

        // 3. Kembalikan respons 'No Content' (sukses)
        return response()->json(null, 204); 
    }
}
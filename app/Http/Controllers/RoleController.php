<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Simpan Role Baru untuk Tim Tertentu.
     */
    public function store(Request $request, Team $team)
    {
        // 1. Validasi
        $request->validate([
            'name' => 'required|string|max:50',
            'permissions' => 'required|array', // Array ID permission
            'permissions.*' => 'exists:permissions,id'
        ]);

        // 2. Cek Otorisasi (Hanya Leader yang boleh buat role)
        if ($team->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 3. Buat Role
        $role = Role::create([
            'team_id' => $team->id,
            'name' => $request->name,
        ]);

        // 4. Sambungkan Role dengan Permissions (Tabel Pivot)
        $role->permissions()->attach($request->permissions);

        // 5. Return JSON (Role baru beserta permissions-nya)
        return response()->json($role->load('permissions'), 201);
    }
}
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TeamController; 
use App\Http\Controllers\RoleController; 
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    // Ambil user yang sedang login
    $user = Auth::user();

    // 1. Ambil semua tugas personal (dengan relasi status)
    $personalTasks = $user->personalTasks()
                        ->with('status') // Eager load relasi status
                        ->latest()
                        ->get();
    
    // 2. Ambil semua status kustom personal
    $personalStatuses = $user->personalStatuses()->get();
    
    // 3. Kirim KEDUA data tersebut ke view
    return view('dashboard', [
        'tasks' => $personalTasks,
        'statuses' => $personalStatuses,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// === GRUP UNTUK TASK & PROFILE ===
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tasks
    Route::get('/tasks/personal/create', [TaskController::class, 'createPersonalTask'])->name('tasks.createPersonal');
    Route::post('/tasks/personal', [TaskController::class, 'storePersonalTask'])->name('tasks.storePersonal');
    Route::delete('/tasks/personal/{task}', [TaskController::class, 'destroyPersonalTask'])->name('tasks.destroyPersonal');
    Route::put('/tasks/personal/{task}', [TaskController::class, 'updatePersonalTask'])->name('tasks.updatePersonal');
});

// === GRUP BARU UNTUK STATUS ===
Route::middleware(['auth', 'verified'])->prefix('statuses')->name('statuses.')->group(function () {
    
    // 1. (GET) /statuses/personal -> name('statuses.getPersonal')
    Route::get('/personal', [StatusController::class, 'getPersonalStatuses'])->name('getPersonal');
    // 2. (POST) /statuses/personal -> name('statuses.storePersonal')
    Route::post('/personal', [StatusController::class, 'storePersonalStatus'])->name('storePersonal');
    // 3. (PUT) /statuses/personal/{status} -> name('statuses.updatePersonal')
    Route::put('/personal/{status}', [StatusController::class, 'updatePersonalStatus'])->name('updatePersonal');
    // 4. (DELETE) /statuses/personal/{status} -> name('statuses.destroyPersonal')
    Route::delete('/personal/{status}', [StatusController::class, 'destroyPersonalStatus'])->name('destroyPersonal');
});

// === ROUTE UNTUK MANAJEMEN TIM ===
Route::middleware(['auth', 'verified'])->prefix('teams')->name('teams.')->group(function () {
    
    // 1. Halaman Form "Buat Tim Baru"
    Route::get('/create', [TeamController::class, 'create'])->name('create');
    // 2. Proses Simpan Tim Baru
    Route::post('/', [TeamController::class, 'store'])->name('store');
    // 3. Dashboard Khusus Tim
    Route::get('/{team}', [TeamController::class, 'show'])->name('show');
    // 4. Hapus Tim
    Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');
    // Invite member
    Route::post('/{team}/members', [TeamController::class, 'storeMember'])->name('members.store');
    // Remove member
    Route::delete('/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('members.destroy');
    // Simpan tugas tim
    Route::post('/{team}/tasks', [TaskController::class, 'storeTeamTask'])->name('tasks.store');
    // Update Tugas Tim
    Route::put('/tasks/{task}', [TaskController::class, 'updateTeamTask'])->name('tasks.update');
    // Hapus Tugas Tim
    Route::delete('/tasks/{task}', [TaskController::class, 'destroyTeamTask'])->name('tasks.destroy');
    // Tambah role baru
    Route::post('/{team}/roles', [RoleController::class, 'store'])->name('roles.store');
});


require __DIR__.'/auth.php';
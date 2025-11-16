<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\StatusController; // Pastikan ini ada
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

// === GRUP BARU UNTUK STATUS (INI PERBAIKANNYA) ===
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


require __DIR__.'/auth.php';
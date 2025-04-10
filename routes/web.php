<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::Class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


Route::middleware('auth')->group(function () {
    Route::get('/chat/{user}', [ChatController::class, 'index'])->name('chat');
    Route::get('/messages/{user}', [ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/messages/{user}', [ChatController::class, 'sendMessage'])->name('chat.send');
});

// Route::middleware(['auth'])->group(function () {
//     Route::get('/chat/{user}', [ChatController::class, 'show'])->name('chat');

//     Route::get('/messages/{user}', [ChatController::class, 'getMessages']);
//     Route::post('/messages/{user}', [ChatController::class, 'sendMessage']);
// });
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/', [WhatsappController::class, 'index'])->name('index');
        Route::post('/connect', [WhatsappController::class, 'connect'])->name('connect');
        Route::get('/qr-code', [WhatsappController::class, 'qrCode'])->name('qr-code');
        Route::get('/status', [WhatsappController::class, 'status'])->name('status');
        Route::post('/disconnect', [WhatsappController::class, 'disconnect'])->name('disconnect');
    });
});

Route::post('/webhooks/whatsapp', [WhatsappController::class, 'webhook'])->name('whatsapp.webhook');

require __DIR__.'/auth.php';

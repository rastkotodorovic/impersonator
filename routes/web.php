<?php

use App\Http\Controllers\AutoReplyContactController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OpenAIAuthController;
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

        Route::prefix('auto-reply')->name('auto-reply.')->group(function () {
            Route::get('/', [AutoReplyContactController::class, 'index'])->name('index');
            Route::post('/contacts', [AutoReplyContactController::class, 'store'])->name('contacts.store');
            Route::patch('/contacts/{contact}/toggle', [AutoReplyContactController::class, 'toggle'])->name('contacts.toggle');
            Route::delete('/contacts/{contact}', [AutoReplyContactController::class, 'destroy'])->name('contacts.destroy');
        });
    });

    Route::prefix('openai')->name('openai.')->group(function () {
        Route::get('/redirect', [OpenAIAuthController::class, 'redirect'])->name('redirect');
        Route::get('/callback', [OpenAIAuthController::class, 'callback'])->name('callback');
        Route::post('/api-key', [OpenAIAuthController::class, 'saveApiKey'])->name('api-key.store');
        Route::delete('/credential', [OpenAIAuthController::class, 'removeCredential'])->name('credential.destroy');
    });

    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::post('/send', [ChatController::class, 'send'])->name('send');
    });
});

Route::post('/webhooks/whatsapp', [WhatsappController::class, 'webhook'])->name('whatsapp.webhook');

require __DIR__.'/auth.php';

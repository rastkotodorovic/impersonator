<?php

use App\Http\Controllers\AiCredentialController;
use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\AiTraceController;
use App\Http\Controllers\AutoReplyContactController;
use App\Http\Controllers\MessageImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'urls' => [
            'whatsapp' => route('whatsapp.index'),
            'imports' => route('imports.index'),
            'ai' => route('ai.index'),
            'profile' => route('profile.edit'),
        ],
    ]);
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
            Route::get('/logs/{log}/trace', [AiTraceController::class, 'show'])->name('logs.trace');
            Route::post('/contacts', [AutoReplyContactController::class, 'store'])->name('contacts.store');
            Route::patch('/contacts/{contact}/toggle', [AutoReplyContactController::class, 'toggle'])->name('contacts.toggle');
            Route::delete('/contacts/{contact}', [AutoReplyContactController::class, 'destroy'])->name('contacts.destroy');
        });
    });

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/', [AiSettingsController::class, 'index'])->name('index');
        Route::get('/openai/redirect', [AiCredentialController::class, 'redirect'])->name('openai.redirect');
        Route::get('/openai/callback', [AiCredentialController::class, 'callback'])->name('openai.callback');
        Route::post('/openai/api-key', [AiCredentialController::class, 'saveApiKey'])->name('openai.api-key.store');
        Route::post('/openai/models', [AiCredentialController::class, 'saveOpenAiModels'])->name('openai.models.store');
        Route::post('/anthropic/api-key', [AiCredentialController::class, 'saveAnthropicApiKey'])->name('anthropic.api-key.store');
        Route::post('/anthropic/models', [AiCredentialController::class, 'saveAnthropicModels'])->name('anthropic.models.store');
        Route::post('/voyage/api-key', [AiCredentialController::class, 'saveVoyageApiKey'])->name('voyage.api-key.store');
        Route::post('/voyage/models', [AiCredentialController::class, 'saveVoyageModels'])->name('voyage.models.store');
        Route::post('/providers', [AiCredentialController::class, 'saveProviders'])->name('providers.update');
        Route::delete('/openai/credential', [AiCredentialController::class, 'removeCredential'])->name('openai.credential.destroy');
        Route::delete('/anthropic/credential', [AiCredentialController::class, 'removeAnthropicCredential'])->name('anthropic.credential.destroy');
        Route::delete('/voyage/credential', [AiCredentialController::class, 'removeVoyageCredential'])->name('voyage.credential.destroy');
    });

    Route::prefix('imports')->name('imports.')->group(function () {
        Route::get('/messages', [MessageImportController::class, 'index'])->name('index');
        Route::post('/messages', [MessageImportController::class, 'store'])->name('store');
    });
});

Route::post('/webhooks/whatsapp', [WhatsappController::class, 'webhook'])->name('whatsapp.webhook');

require __DIR__.'/auth.php';

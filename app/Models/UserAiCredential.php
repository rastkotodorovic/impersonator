<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiCredential extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'auth_method',
        'api_key',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'external_user_id',
        'external_email',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isProvider(string $provider): bool
    {
        return $this->provider === $provider;
    }

    public function isApiKey(): bool
    {
        return $this->auth_method === 'api_key';
    }

    public function isOAuth(): bool
    {
        return $this->auth_method === 'oauth';
    }

    public function isTokenExpired(): bool
    {
        if (! $this->isOAuth() || ! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    public function hasValidCredential(): bool
    {
        if ($this->isApiKey()) {
            return ! empty($this->api_key);
        }

        return ! empty($this->access_token);
    }

    public function getActiveToken(): ?string
    {
        if ($this->isApiKey()) {
            return $this->api_key;
        }

        return $this->access_token;
    }
}

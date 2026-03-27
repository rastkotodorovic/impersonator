<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOpenaiCredential extends Model
{
    protected $fillable = [
        'user_id',
        'auth_method',
        'api_key',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_token_expires_at',
        'openai_user_id',
        'openai_email',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'oauth_access_token' => 'encrypted',
            'oauth_refresh_token' => 'encrypted',
            'oauth_token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        if (! $this->isOAuth() || ! $this->oauth_token_expires_at) {
            return false;
        }

        return $this->oauth_token_expires_at->isPast();
    }

    public function hasValidCredential(): bool
    {
        if ($this->isApiKey()) {
            return ! empty($this->api_key);
        }

        return ! empty($this->oauth_access_token);
    }

    public function getActiveToken(): ?string
    {
        if ($this->isApiKey()) {
            return $this->api_key;
        }

        return $this->oauth_access_token;
    }
}

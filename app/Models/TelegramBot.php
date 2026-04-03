<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramBot extends Model
{
    protected $fillable = [
        'user_id',
        'bot_token',
        'bot_id',
        'bot_username',
        'webhook_secret',
        'status',
        'connected_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'bot_token' => 'encrypted',
            'connected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected' && ! empty($this->bot_token);
    }
}

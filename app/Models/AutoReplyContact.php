<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoReplyContact extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'phone_number',
        'identifier',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/@.*$/', '', $phone);
    }

    public static function normalizeIdentifier(string $channel, string $value): string
    {
        return match ($channel) {
            'telegram' => strtolower(ltrim(trim($value), '@')),
            default => static::normalizePhone($value),
        };
    }

    public function wahaPhone(): string
    {
        return $this->phone_number . '@s.whatsapp.net';
    }

    public function channelLabel(): string
    {
        return $this->channel === 'telegram' ? 'Telegram' : 'WhatsApp';
    }
}

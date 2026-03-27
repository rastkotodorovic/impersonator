<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoReplyContact extends Model
{
    protected $fillable = [
        'user_id',
        'phone_number',
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

    public function wahaPhone(): string
    {
        return $this->phone_number . '@s.whatsapp.net';
    }
}

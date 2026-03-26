<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_name',
        'status',
        'phone_number',
        'display_name',
        'connected_at',
        'disconnected_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isQrPending(): bool
    {
        return $this->status === 'qr_pending';
    }
}

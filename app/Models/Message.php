<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_name',
        'is_from_me',
        'content',
        'timestamp_ms',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_from_me' => 'boolean',
            'timestamp_ms' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function embedding(): HasOne
    {
        return $this->hasOne(MessageEmbedding::class);
    }
}

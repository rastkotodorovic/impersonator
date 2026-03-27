<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'thread_path',
        'title',
        'source',
        'participants',
        'participant_count',
        'is_group_chat',
    ];

    protected function casts(): array
    {
        return [
            'participants' => 'array',
            'is_group_chat' => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}

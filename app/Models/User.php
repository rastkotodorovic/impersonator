<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'chat_provider', 'embedding_provider', 'channel', 'identifier', 'phone_number', 'preferred_conversation_id', 'is_active', 'ai_additional_instructions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function whatsappSession(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WhatsappSession::class);
    }

    public function aiCredentials(): HasMany
    {
        return $this->hasMany(UserAiCredential::class);
    }

    public function aiCredentialFor(string $provider): ?UserAiCredential
    {
        if ($this->relationLoaded('aiCredentials')) {
            return $this->aiCredentials->firstWhere('provider', $provider);
        }

        return $this->aiCredentials()->where('provider', $provider)->first();
    }

    public function autoReplyContacts(): HasMany
    {
        return $this->hasMany(AutoReplyContact::class);
    }

    public function whatsappMessageLogs(): HasMany
    {
        return $this->hasMany(WhatsappMessageLog::class);
    }

    public function aiTraces(): HasMany
    {
        return $this->hasMany(AiTrace::class);
    }
}

<?php

namespace App\Modules\Messaging\Models;

use App\Support\Traits\HasUuid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    use HasUuid;

    protected $connection = 'tenant';
    protected $table = 'conversations';

    protected $fillable = [
        'entity_type', 'entity_id', 'subject', 'created_by', 'is_group', 'closed_at'
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'closed_at' => 'datetime',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * L'entité métier liée à cette conversation (polymorphe).
     * Ex: Reservation, Contract, Hotel, etc.
     */
    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    /**
     * L'utilisateur qui a créé la conversation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Les participants de la conversation.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Les messages de la conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Le dernier message de la conversation (pour l'affichage).
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope : conversations ouvertes (non fermées).
     */
    public function scopeOpen($query)
    {
        return $query->whereNull('closed_at');
    }

    /**
     * Scope : conversations d'un utilisateur (où il est participant).
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    // ============================================================
    // METHODS
    // ============================================================

    /**
     * Ajouter un participant à la conversation.
     */
    public function addParticipant(string $userId, string $role = 'member'): ConversationParticipant
    {
        return $this->participants()->create([
            'user_id' => $userId,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * Vérifier si un utilisateur est participant.
     */
    public function hasParticipant(string $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * Fermer la conversation.
     */
    public function close(): self
    {
        $this->update(['closed_at' => now()]);
        return $this;
    }

    /**
     * Rouvrir la conversation.
     */
    public function reopen(): self
    {
        $this->update(['closed_at' => null]);
        return $this;
    }
}
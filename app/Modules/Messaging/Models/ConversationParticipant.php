<?php

namespace App\Modules\Messaging\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $connection = 'tenant';
    protected $table = 'conversation_participants';

    protected $fillable = [
        'conversation_id', 'user_id', 'role', 'last_read_at', 'joined_at', 'left_at'
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================================================
    // METHODS
    // ============================================================

    /**
     * Marquer la conversation comme lue pour ce participant.
     */
    public function markAsRead(): self
    {
        $this->update(['last_read_at' => now()]);
        return $this;
    }

    /**
     * Vérifier si le participant est actif (n'a pas quitté).
     */
    public function isActive(): bool
    {
        return is_null($this->left_at);
    }
}

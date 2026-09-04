<?php

namespace App\Modules\Messaging\Models;

use App\Support\Traits\HasUuid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasUuid;

    protected $connection = 'tenant';
    protected $table = 'messages';

    protected $fillable = [
        'conversation_id', 'sender_id', 'content', 'is_read', 'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ============================================================
    // METHODS
    // ============================================================

    /**
     * Marquer le message comme lu.
     */
    public function markAsRead(): self
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
        return $this;
    }

    /**
     * Vérifier si le message a été lu.
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    public function attachments()
{
    return $this->hasMany(MessageAttachment::class);
}
}

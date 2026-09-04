<?php

namespace App\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $connection = 'tenant';
    protected $table = 'message_attachments';

    protected $fillable = [
        'message_id', 'file_name', 'file_path', 'mime_type', 'size'
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    // Accesseur pour l'URL publique du fichier
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    // Accesseur pour la taille formatée (ex: "2.5 MB")
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

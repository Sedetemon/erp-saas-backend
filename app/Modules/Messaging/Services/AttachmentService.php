<?php

namespace App\Modules\Messaging\Services;

use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Téléverser un fichier attaché à un message
     */
    public function upload(Message $message, UploadedFile $file): MessageAttachment
    {
        // Générer un nom unique pour le fichier
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $path = 'messages/' . $message->id . '/' . $fileName;

        // Stocker le fichier
        Storage::disk('public')->put($path, file_get_contents($file));

        // Créer l'enregistrement en base
        return $message->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Supprimer un fichier attaché
     */
    public function delete(MessageAttachment $attachment): void
    {
        // Supprimer le fichier physique
        Storage::disk('public')->delete($attachment->file_path);

        // Supprimer l'enregistrement en base
        $attachment->delete();
    }
}

<?php

namespace App\Modules\Messaging\Services;

use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Models\ConversationParticipant;
use App\Modules\Messaging\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exceptions\ValidationException;

class MessagingService
{
    /**
     * Créer une nouvelle conversation.
     */
    public function createConversation(array $data): Conversation
    {
        return DB::connection('tenant')->transaction(function () use ($data) {
            // 1. Créer la conversation
            $conversation = Conversation::create([
                'entity_type' => $data['entity_type'] ?? null,
                'entity_id' => $data['entity_id'] ?? null,
                'subject' => $data['subject'] ?? 'Conversation',
                'created_by' => $data['created_by'],
                'is_group' => $data['is_group'] ?? false,
            ]);

            // 2. Ajouter les participants
            $participants = $data['participants'] ?? [];
            $createdBy = $data['created_by'];

            // Ajouter le créateur s'il n'est pas déjà dans la liste
            if (!in_array($createdBy, $participants)) {
                $participants[] = $createdBy;
            }

            // Ajouter chaque participant (rôle member sauf pour le créateur)
            foreach ($participants as $userId) {
                $role = ($userId === $createdBy) ? 'owner' : 'member';
                $conversation->addParticipant($userId, $role);
            }

            // Si c'est une conversation directe (2 participants) et pas de sujet défini
            if (count($participants) === 2 && empty($data['subject'])) {
                // On peut définir un sujet par défaut
                $conversation->subject = 'Conversation privée';
                $conversation->save();
            }

            return $conversation;
        });
    }

    /**
     * Envoyer un message dans une conversation.
     */
    public function sendMessage(string $conversationId, string $senderId, string $content): Message
{
    $conversation = Conversation::findOrFail($conversationId);

    if (!$conversation->hasParticipant($senderId)) {
        throw new ValidationException('Vous n\'êtes pas participant de cette conversation.');
    }

    if ($conversation->closed_at) {
        throw new ValidationException('Cette conversation est fermée.');
    }

    return DB::connection('tenant')->transaction(function () use ($conversation, $senderId, $content) {
        $message = $conversation->messages()->create([
            'sender_id' => $senderId,
            'content' => $content,
        ]);

        // --- Envoi des notifications aux autres participants ---
        $otherParticipants = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->whereNull('left_at')
            ->get();

        foreach ($otherParticipants as $participant) {
            // Récupérer l'utilisateur (modèle User)
            $user = \App\Models\User::find($participant->user_id);
            if ($user) {
                // Envoyer la notification
                $user->notify(new \App\Notifications\NewMessageNotification($message));
            }
        }

        return $message;
    });
}

    /**
     * Marquer tous les messages d'une conversation comme lus pour un utilisateur.
     */
    public function markAllAsRead(string $conversationId, string $userId): void
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $participant->markAsRead();

        // Marquer les messages individuels comme lus (sauf ceux envoyés par l'utilisateur)
        Message::where('conversation_id', $conversationId)
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Récupérer les conversations d'un utilisateur avec le dernier message.
     */
    public function getUserConversations(string $userId)
    {
        return Conversation::forUser($userId)
            ->open()
            ->with(['participants', 'lastMessage'])
            ->get();
    }

    /**
     * Compter les messages non lus pour un utilisateur.
     */
    public function countUnreadMessages(string $userId): int
    {
        // Récupérer les conversations de l'utilisateur
        $conversationIds = ConversationParticipant::where('user_id', $userId)->pluck('conversation_id');

        return Message::whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Récupérer les messages d'une conversation (paginés).
     */
    public function getConversationMessages(string $conversationId, int $page = 1, int $perPage = 20)
    {
        return Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Ajouter un participant à une conversation existante.
     */
    public function addParticipant(string $conversationId, string $userId, string $role = 'member'): ConversationParticipant
    {
        $conversation = Conversation::findOrFail($conversationId);

        if ($conversation->hasParticipant($userId)) {
            throw new ValidationException('Cet utilisateur est déjà participant.');
        }

        return $conversation->addParticipant($userId, $role);
    }

    /**
     * Retirer un participant d'une conversation.
     */
    public function removeParticipant(string $conversationId, string $userId): void
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $participant->update(['left_at' => now()]);
    }

    /**
 * Rechercher des messages dans les conversations de l'utilisateur
 */
public function searchMessages(string $userId, string $query, int $page = 1, int $perPage = 20)
{
    // Récupérer les IDs des conversations où l'utilisateur est participant
    $conversationIds = ConversationParticipant::where('user_id', $userId)
        ->whereNull('left_at')
        ->pluck('conversation_id');

    if ($conversationIds->isEmpty()) {
        return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $page);
    }

    return Message::whereIn('conversation_id', $conversationIds)
        ->where('content', 'LIKE', '%' . addcslashes($query, '%_') . '%')
        ->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);
}
}

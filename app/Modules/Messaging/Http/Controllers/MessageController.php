<?php

namespace App\Modules\Messaging\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageAttachment;
use App\Modules\Messaging\Services\AttachmentService;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected MessagingService $messagingService;
    protected AttachmentService $attachmentService;

    public function __construct(
        MessagingService $messagingService,
        AttachmentService $attachmentService
    ) {
        $this->messagingService = $messagingService;
        $this->attachmentService = $attachmentService;
    }

    /**
     * Lister les messages d'une conversation (paginé).
     */
    public function index(Request $request, string $conversationId)
    {
        // Vérifier que l'utilisateur est participant
        $conversation = Conversation::findOrFail($conversationId);
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'error' => 'Accès interdit',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $messages = $this->messagingService->getConversationMessages(
            $conversationId,
            $request->input('page', 1),
            $request->input('per_page', 20)
        );

        return response()->json($messages);
    }

    /**
     * Envoyer un message dans une conversation.
     */
    public function store(Request $request, string $conversationId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:65535',
        ]);

        $message = $this->messagingService->sendMessage(
            $conversationId,
            $request->user()->id,
            $validated['content']
        );

        return response()->json([
            'data' => $message,
        ], 201);
    }

    /**
     * Marquer un message spécifique comme lu.
     */
    public function markRead(string $id, Request $request)
    {
        $message = Message::findOrFail($id);

        // Vérifier que l'utilisateur est participant de la conversation
        $conversation = $message->conversation;
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'error' => 'Accès interdit',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $message->markAsRead();

        return response()->json([
            'data' => $message,
        ]);
    }

    /**
     * Supprimer un message (seul l'expéditeur peut le faire).
     */
    public function destroy(string $id, Request $request)
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Vous ne pouvez pas supprimer ce message',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $message->delete();

        return response()->json(null, 204);
    }

    /**
     * Téléverser un fichier attaché à un message
     */
    public function uploadAttachment(Request $request, string $messageId)
    {
        $message = Message::with('conversation')->findOrFail($messageId);

        // Vérifier que l'utilisateur est participant de la conversation
        if (!$message->conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'error' => 'Vous n\'êtes pas participant de cette conversation',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10 MB max
        ]);

        $attachment = $this->attachmentService->upload($message, $request->file('file'));

        return response()->json([
            'data' => $attachment,
        ], 201);
    }

    /**
     * Supprimer un fichier attaché
     */
    public function deleteAttachment(string $id, Request $request)
    {
        $attachment = MessageAttachment::with('message.conversation')->findOrFail($id);

        // Vérifier que l'utilisateur est participant
        if (!$attachment->message->conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'error' => 'Vous n\'êtes pas participant de cette conversation',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        // Seul l'expéditeur du message peut supprimer un fichier attaché
        if ($attachment->message->sender_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Vous ne pouvez pas supprimer ce fichier',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $this->attachmentService->delete($attachment);

        return response()->json(null, 204);
    }

    /**
     * Rechercher des messages par mot-clé
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $messages = $this->messagingService->searchMessages(
            $request->user()->id,
            $request->q,
            $request->input('page', 1),
            $request->input('per_page', 20)
        );

        return response()->json($messages);
    }
}

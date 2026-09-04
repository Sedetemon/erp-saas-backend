<?php

namespace App\Modules\Messaging\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    protected MessagingService $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Liste des conversations de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $conversations = $this->messagingService->getUserConversations($userId);

        return response()->json([
            'data' => $conversations,
        ]);
    }

    /**
     * Créer une nouvelle conversation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
            'subject' => 'nullable|string|max:255',
            'entity_type' => 'nullable|string|max:100',
            'entity_id' => 'nullable|string|max:36',
            'is_group' => 'boolean',
        ]);

        $validated['created_by'] = $request->user()->id;

        $conversation = $this->messagingService->createConversation($validated);

        return response()->json([
            'data' => $conversation,
        ], 201);
    }

    /**
     * Afficher une conversation spécifique avec ses messages.
     */
    public function show(string $id, Request $request)
    {
        $conversation = Conversation::with(['participants', 'messages.sender'])
            ->findOrFail($id);

        // Vérifier que l'utilisateur est participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'error' => 'Accès interdit à cette conversation',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        return response()->json([
            'data' => $conversation,
        ]);
    }

    /**
     * Marquer tous les messages comme lus.
     */
    public function markAsRead(string $id, Request $request)
    {
        $this->messagingService->markAllAsRead($id, $request->user()->id);

        return response()->json([
            'message' => 'Messages marqués comme lus',
        ]);
    }

    /**
     * Fermer une conversation (seul le propriétaire ou admin peut le faire).
     */
    public function close(string $id, Request $request)
    {
        $conversation = Conversation::findOrFail($id);

        $participant = $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($participant->role, ['owner', 'admin'])) {
            return response()->json([
                'error' => 'Seul le propriétaire ou un admin peut fermer la conversation',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $conversation->close();

        return response()->json([
            'message' => 'Conversation fermée',
            'data' => $conversation,
        ]);
    }

    /**
     * Ajouter un participant à une conversation existante.
     */
    public function addParticipant(string $id, Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:member,admin',
        ]);

        $conversation = Conversation::findOrFail($id);

        // Vérifier que l'utilisateur actuel est admin/owner
        $participant = $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($participant->role, ['owner', 'admin'])) {
            return response()->json([
                'error' => 'Action non autorisée',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $newParticipant = $this->messagingService->addParticipant(
            $id,
            $request->user_id,
            $request->role ?? 'member'
        );

        return response()->json([
            'data' => $newParticipant,
        ], 201);
    }

    /**
     * Retirer un participant de la conversation.
     */
    public function removeParticipant(string $id, Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $conversation = Conversation::findOrFail($id);

        // Vérifier que l'utilisateur actuel est admin/owner
        $participant = $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($participant->role, ['owner', 'admin'])) {
            return response()->json([
                'error' => 'Action non autorisée',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $this->messagingService->removeParticipant($id, $request->user_id);

        return response()->json([
            'message' => 'Participant retiré',
        ]);
    }
}

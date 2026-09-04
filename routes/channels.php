<?php

use App\Modules\Messaging\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Vérifier que l'utilisateur est participant de la conversation
    $conversation = Conversation::find($conversationId);
    return $conversation && $conversation->hasParticipant($user->id);
});

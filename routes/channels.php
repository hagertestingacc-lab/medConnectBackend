<?php

use App\Models\Chat\Conversation;
use Illuminate\Support\Facades\Broadcast;

/* Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
 */

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (!$conversation) return false;

    // Only participants can subscribe to this channel
    return $conversation->participant_one == $user->id
        || $conversation->participant_two == $user->id;
});

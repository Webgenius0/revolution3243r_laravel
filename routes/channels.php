<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Room;

/* Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}); */

// Broadcast::channel('test-notify.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// Broadcast::channel('notify.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// /*
// # chat
// */

// Broadcast::channel('chat-room.{room_id}', function ($user, $room_id) {
//     $room = Room::find($room_id);
//     return (int) $user->id === (int) $room?->user_one_id || (int) $user->id === (int) $room?->user_two_id;
// });

// Broadcast::channel('chat-receiver.{receiver_id}', function ($user, $receiver_id) {
//     return (int) $user->id === (int) $receiver_id;
// });

// Broadcast::channel('chat-sender.{sender_id}', function ($user, $sender_id) {
//     return (int) $user->id === (int) $sender_id;
// });


Broadcast::channel('user.{id}', function ($user, $id) {
    // Only allow the authenticated user to listen to their own channel
    return (int) $user->id === (int) $id;
});
Broadcast::channel('admin.{id}', function ($user, $id) {
    // Only allow the authenticated user to listen to their own channel
    return (int) $user->id === (int) $id;
});

<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Room;


Broadcast::channel('sender_id.{id}', function ($user, $id) {
    return (int) 108 === (int) $id;
});


Broadcast::channel('receiver_id.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('admin.{id}', function ($user, $id) {
    // Only allow the authenticated user to listen to their own channel
    return (int) $user->id === (int) $id;
});

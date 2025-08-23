<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent as EventsMessageSent;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;

class OnetoOneChatCOntroller extends Controller
{
    //

    public function messages($userId)
    {
        $authId = auth()->id();

        $messages = Chat::where(function ($q) use ($authId, $userId) {
            $q->where('sender_id', $authId)
                ->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($authId, $userId) {
            $q->where('sender_id', $userId)
                ->where('receiver_id', $authId);
        })->orderBy('created_at', 'asc')->get();
        $messages->load('room');

        return response()->json([
            'success' => true,
            'message' => 'Messages fetched successfully',
            'data' => $messages
        ]);
    }


    public function conversation()
    {
        $authId = auth()->id();


        $rooms = Room::where('user_one_id', $authId)
            ->orWhere('user_two_id', $authId)
            ->get();
        $user = $rooms->map(function ($room) use ($authId) {
            return $room->user_one_id == $authId ? $room->user_two_id : $room->user_one_id;
        })->values();


        $user = User::find($user);

        return response()->json([
            'success' => true,
            'message' => 'All chat users fetched successfully',
            'data' => $user
        ]);
    }


    // Send a new message
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'text'        => 'required|string',
            'file'        => 'nullable|file|max:10240', // optional, max 10MB
        ]);

        $sender_id   = auth()->id();
        $receiver_id = $request->receiver_id;

        // Handle file upload
        $filePath = null;
        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $fileName     = getFileName($uploadedFile); // helper function
            $filePath     = Helper::fileUpload($uploadedFile, 'media', $fileName);
        }

        // ✅ Find or create room
        $room = Room::where(function ($query) use ($receiver_id, $sender_id) {
            $query->where('user_one_id', $receiver_id)
                ->where('user_two_id', $sender_id);
        })->orWhere(function ($query) use ($receiver_id, $sender_id) {
            $query->where('user_one_id', $sender_id)
                ->where('user_two_id', $receiver_id);
        })->first();

        if (!$room) {
            $room = Room::create([
                'user_one_id' => $sender_id,
                'user_two_id' => $receiver_id,
            ]);

            // Attach both users with default notifications = true
            $room->users()->attach([$sender_id, $receiver_id], ['notifications' => 1]);
        }

        // ✅ Create chat message with room_id
        $message = Chat::create([
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'room_id'     => $room->id,
            'text'        => $request->text,
            'file'        => $filePath,
            'status'      => 'unread',
        ]);

        // ✅ Check receiver notifications before broadcasting
        $receiverHasNotifications = $room->users()
            ->where('user_id', $receiver_id)
            ->wherePivot('notifications', 1)
            ->exists();

        broadcast(new EventsMessageSent($message))->toOthers();


        if ($receiverHasNotifications) {
   if ($receiverHasNotifications) {
        $receiver = User::find($receiver_id);
        if ($receiver) {
            $receiver->notify(new \App\Notifications\NewMessageNotification($message));
        }
    }
            if ($receiverHasNotifications) {
                broadcast(new EventsMessageSent($message))->toOthers();

                $user = User::find($receiver_id); // fetch receiver
                if ($user && $user->firebaseTokens) {
                    $notifyData = [
                        'title' => "New Message",
                        'body'  => $request->text,
                        'icon'  => config('settings.logo')
                    ];
                    foreach ($user->firebaseTokens as $firebaseToken) {
                        Helper::sendNotifyMobile($firebaseToken->token, $notifyData);
                    }
                }
            }
        }

        $message->load('room');

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully!',
            'data'    => $message,
        ]);
    }
    public function updateNotifications(Request $request, $roomId)
    {
        $request->validate([
            'notifications' => 'required|boolean',
        ]);

        $userId = auth()->id();
        $room   = Room::find($roomId);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
            ], 404);
        }

        // update the pivot table for this user
        $room->users()->updateExistingPivot($userId, [
            'notifications' => $request->notifications
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications updated successfully',
            'data'    => [
                'room_id'       => $roomId,
                'user_id'       => $userId,
                'notifications' => $request->notifications
            ]
        ]);
    }

    public function markAsRead($userId)
    {
        $authId = auth()->id();

        // Update unread messages
        $updatedCount = Chat::where('sender_id', $userId)
            ->where('receiver_id', $authId)
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        if ($updatedCount > 0) {
            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'updated_count' => $updatedCount,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No unread messages found',
            ]);
        }
    }

    // Optional: fetch list of users to chat with
    public function users()
    {
        $authId = auth()->id();
        $users = User::where('id', '!=', $authId)->get();
        return response()->json($users);
    }
}

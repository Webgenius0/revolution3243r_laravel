<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    //

    public function profile($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        $sentRequest = FriendRequest::where('sender_id', auth()->user()->id)
            ->where('receiver_id', $id)
            ->exists();

        // ret

        $posts = $user->posts()
            ->withCount('likes')                 // Correct withCount syntax
            ->with(['media', 'comments', 'likes'])
            ->get();

        // Add is_following flag for each post
        foreach ($posts as $post) {
            $post->is_following = auth()->user()->followings()
                ->where('following_id', $post->user_id) // or $post->user->id
                ->exists();
        }

        $info = [
            'id' => $user->id,
            'name' => $user->name,
            'bio' => $user->bio,
            'avatar' => $user->avatar ? url($user->avatar) : null,
            'total_post' => $user->posts->count(),
            'following' => $user->followings->count(),
            'followers' => $user->followers->count(),
            'friend_request_sent' => $sentRequest ?? false, // make sure this variable is defined
            'posts' => $posts
        ];

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data' => $info
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data' => $info
        ]);
    }


    public function send($receiverId)
    {
        $senderId = Auth::id();

        // Prevent sending to self
        if ($senderId == $receiverId) {
            return response()->json(['success' => false, 'message' => 'You cannot send request to yourself'], 400);
        }

        // Check if already exists
        $exists = FriendRequest::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('status', 'pending')
            ->exists();

        $user = User::find($receiverId);
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Request already sent', 'data' => $user], 400);
        }

        FriendRequest::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
        ]);


        return response()->json(['success' => true, 'message' => 'Friend request sent', 'data' => $user]);
    }

    // Accept request
    public function accept($id)
    {
        $request = FriendRequest::findOrFail($id);

        if ($request->receiver_id != Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Not authorized'], 403);
        }

        $request->update(['status' => 'accepted']);

        return response()->json(['success' => true, 'message' => 'Friend request accepted']);
    }
    public function requests()
    {
        $userId = Auth::id();

        $requests = FriendRequest::with('sender')
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'sender_name' => $req->sender->name,
                    'sender_id' => $req->sender->id,
                    'sender_avatar' => $req->sender->avatar ? url($req->sender->avatar) : null,
                    'status' => $req->status,
                    'sent_at' => $req->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Pending friend requests retrieved successfully',
            'data' => $requests,
        ]);
    }
    public function friends()
    {
        $userId = Auth::id();

        // Friends where user is the receiver
        $friendsAsReceiver = FriendRequest::with('sender')
            ->where('receiver_id', $userId)
            ->where('status', 'accepted')
            ->get()
            ->map(function ($req) {
                return [
                    'friend_id' => $req->sender->id,
                    'friend_name' => $req->sender->name,
                    'friend_avatar' => $req->sender->avatar ? url($req->sender->avatar) : null,
                    'friend_since' => $req->updated_at->diffForHumans(),
                ];
            });

        // Friends where user is the sender
        $friendsAsSender = FriendRequest::with('receiver')
            ->where('sender_id', $userId)
            ->where('status', 'accepted')
            ->get()
            ->map(function ($req) {
                return [
                    'friend_id' => $req->receiver->id,
                    'friend_name' => $req->receiver->name,
                    'friend_avatar' => $req->receiver->avatar ? url($req->receiver->avatar) : null,
                    'friend_since' => $req->updated_at->diffForHumans(),
                ];
            });

        // Merge both
        $friends = $friendsAsReceiver->merge($friendsAsSender);

        return response()->json([
            'success' => true,
            'message' => 'Friends retrieved successfully',
            'data' => $friends->values(),
        ]);
    }
    // Reject request
    public function reject($id)
    {
        $request = FriendRequest::findOrFail($id);

        if ($request->receiver_id != Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Not authorized'], 403);
        }

        $request->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Friend request rejected']);
    }
}

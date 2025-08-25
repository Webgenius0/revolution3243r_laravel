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

        // Check if authenticated user and profile user are friends
        $isFriend = FriendRequest::where(function ($q) use ($id) {
            $q->where(function ($q2) use ($id) {
                $q2->where('sender_id', auth()->user()->id)
                    ->where('receiver_id', $id);
            })
                ->orWhere(function ($q2) use ($id) {
                    $q2->where('sender_id', $id)
                        ->where('receiver_id', auth()->user()->id);
                });
        })
            ->where('status', 'accepted')
            ->exists();

        // Check if authenticated user has sent a friend request (pending)
        $sentRequest = FriendRequest::where('sender_id', auth()->user()->id)
            ->where('receiver_id', $id)
            ->where('status', 'pending')
            ->exists();

        $posts = $user->posts()
            ->withCount('likes')                 // gives likes_count automatically
            ->with(['media', 'comments.user', 'comments.replies.user'])
            ->get();

        $posts = $posts->map(function ($post) {
            $isFollowing = auth()->user()->followings()
                ->where('following_id', $post->user_id)
                ->exists();

            return [
                'id'           => $post->id,
                'user_id'      => $post->user_id,
                'name'         => optional($post->user)->name,
                'is_liked'     => $post->likes->contains('user_id', auth()->id()),
                'posted_on'     => $post->created_at->diffForHumans(),
                'content'      => $post->content,
                'avatar'       => optional($post->user)->avatar ? url($post->user->avatar) : null,
                'likes_count'  => $post->likes_count,
                'is_following' => $isFollowing,
                'liked_by' => $post->likes->map(function ($like) {
                    return [
                        'post_id'   => $like->post_id,
                        'user_id'   => $like->user_id,
                        'user_name' => optional($like->user)->name,
                        'avatar'    => optional($like->user)->avatar ? url($like->user->avatar) : null,
                    ];
                }),

                'media'        => $post->media,
                'comments'     => $post->comments->map(function ($comment) {
                    return [
                        'id'         => $comment->id,
                        'post_id'    => $comment->post_id,
                        'parent_id'  => $comment->parent_id,
                        'user_id'    => $comment->user_id,
                        'user_name'  => optional($comment->user)->name,
                        'avatar'     => optional($comment->user)->avatar ? url($comment->user->avatar) : null,
                        'comment'    => $comment->comment,
                        'created_at' => $comment->created_at->diffForHumans(),
                        'replies'    => $comment->replies->map(function ($reply) {
                            return [
                                'id'         => $reply->id,
                                'post_id'    => $reply->post_id,
                                'parent_id'  => $reply->parent_id,
                                'user_id'    => $reply->user_id,
                                'user_name'  => optional($reply->user)->name,
                                'avatar'     => optional($reply->user)->avatar ? url($reply->user->avatar) : null,
                                'comment'    => $reply->comment,
                                'created_at' => $reply->created_at->diffForHumans(),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        $info = [
            'id'                  => $user->id,
            'name'                => $user->name,
            'bio'                 => $user->bio,
            'avatar'              => $user->avatar ? url($user->avatar) : null,
            'total_post'          => $user->posts->count(),
            'following'           => $user->followings->count(),
            'followers'           => $user->followers->count(),
            'friend'              => $isFriend,
            'friend_request_sent' => $sentRequest,
            'posts'               => $posts,
        ];

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data'    => $info
        ]);
    }




    public function send($receiverId)
    {
        $senderId = Auth::id();

        // Prevent sending to self
        if ($senderId == $receiverId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a friend request to yourself'
            ], 400);
        }

        // Check if a pending request already exists
        $exists = FriendRequest::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('status', 'pending')
            ->exists();

        $receiver = User::find($receiverId);

        if (!$receiver) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        auth()->user()->followings()->syncWithoutDetaching([$receiverId]);

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Request already sent',
                'data' => $receiver
            ], 400);
        }

        // Create the friend request
        FriendRequest::create([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'status'      => 'pending',
        ]);

        // Optional: automatically follow the user (if your followings table exists)

        return response()->json([
            'success' => true,
            'message' => 'Friend request sent successfully',
            'data'    => $receiver
        ]);
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
    public function requested()
    {
        $userId = Auth::id();

        $requests = FriendRequest::with('sender')
            ->where('sender_id', $userId)
            ->where('status', 'pending')
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'reciver_name' => $req->receiver->name,
                    'reciver_id' => $req->receiver->id,
                    'reciver_avatar' => $req->receiver->avatar ? url($req->receiver->avatar) : null,
                    'status' => $req->status,
                    'sent_at' => $req->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Pending friend requested retrieved successfully',
            'data' => $requests,
        ]);
    }
    public function unfriend($id)
    {
        $authId = auth()->id();

        // Check if the user exists
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Find the friendship in either direction
        $friendship = FriendRequest::where(function ($q) use ($authId, $id) {
            $q->where('sender_id', $authId)
                ->where('receiver_id', $id);
        })
            ->orWhere(function ($q) use ($authId, $id) {
                $q->where('sender_id', $id)
                    ->where('receiver_id', $authId);
            })
            ->where('status', 'accepted')
            ->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'You are not friends with this user'
            ], 400);
        }

        // Delete the friendship
        $friendship->delete();

        // Optional: remove following relationship if you used followings table
        auth()->user()->followings()->detach($id);

        return response()->json([
            'success' => true,
            'message' => 'User unfriended successfully'
        ]);
    }
public function cancelRequest($receiverId)
{
    $senderId = auth()->id();

    // Check if the user exists
    $receiver = User::find($receiverId);
    if (!$receiver) {
        return response()->json([
            'success' => false,
            'message' => 'User not found'
        ], 404);
    }

    // Find the pending friend request sent by the authenticated user
    $request = FriendRequest::where('sender_id', $senderId)
        ->where('receiver_id', $receiverId)
        ->where('status', 'pending')
        ->first();

    if (!$request) {
        return response()->json([
            'success' => false,
            'message' => 'No pending friend request to cancel'
        ], 400);
    }

    // Delete the pending request
    $request->delete();

    // Optional: remove following relationship if added when sending request
    auth()->user()->followings()->detach($receiverId);

    return response()->json([
        'success' => true,
        'message' => 'Friend request cancelled successfully'
    ]);
}

    public function friends()
    {
        $userId = Auth::id();

        // Friends where user is the receiver
        $friendsAsReceiver = FriendRequest::with('sender')
            ->where('receiver_id', $userId)
            ->where('status', 'accepted')
            ->get();
            $friendsAsReceiver->map(function ($req) {
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
            ->get();
            $friendsAsSender->map(function ($req) {
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
            'data' => $friends,
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use Illuminate\Http\Request;
use App\Models\User;

class FollowerController extends Controller
{
    public function toggleFollow($userId)
    {
        $user = auth('api')->user();

        if ($user->id == $userId) {
            return response()->json(['success' => false, 'message' => "You can't follow yourself"], 400);
        }

        // Check if already following
        $isFollowing = $user->followings()->where('following_id', $userId)->exists();

        if ($isFollowing) {
            // Unfollow
            $user->followings()->detach($userId);
            return response()->json(['success' => true, 'message' => 'Unfollowed successfully']);
        } else {
            // Follow
            $user->followings()->attach($userId);
            return response()->json(['success' => true, 'message' => 'Followed successfully']);
        }
    }

    public function followers()
    {
        $user = auth('api')->user();
        $followers = $user->followers->map(function ($follower) {
            return [
                'id' => $follower->id,
                'name' => $follower->name,
                'avatar' => $follower->avatar ? url($follower->avatar) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $followers->count(),

            'message' => 'Followers retrieved successfully.',
            'data' => $followers
        ]);
    }

    public function followings()
    {
        $user = auth('api')->user();
        $followings = $user->followings->map(function ($follow) {
            return [
                'id' => $follow->id,
                'name' => $follow->name,
                'avatar' => $follow->avatar ? url($follow->avatar) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Followings retrieved successfully.',
            'count' => $followings->count(),
            'data' => $followings
        ]);
    }



    public function block($userId)
    {
        $user = auth('api')->user();

        if ($user->id == $userId) {
            return response()->json(['success' => false, 'message' => "You can't block yourself"], 400);
        }

        if ($user->blockedUsers()->where('blocked_user_id', $userId)->exists()) {
            return response()->json(['success' => false, 'message' => 'User already blocked'], 400);
        }

        // Detach follow relationships if any
        $user->followings()->detach($userId);
        $user->followers()->detach($userId);

        FriendRequest::where(function ($q) use ($user, $userId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($user, $userId) {
            $q->where('sender_id', $userId)->where('receiver_id', $user->id);
        })->delete();

        $user->blockedUsers()->attach($userId);

        return response()->json(['success' => true, 'message' => 'User blocked successfully']);
    }

    // Unblock a user
    public function unblock($userId)
    {
        $user = auth('api')->user();
        $user->blockedUsers()->detach($userId);

        return response()->json(['success' => true, 'message' => 'User unblocked successfully']);
    }

    // List of blocked users
    public function blockedUsers()
    {
        $user = auth('api')->user();

        $blocked = $user->blockedUsers->map(function ($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'avatar' => $b->avatar ? url($b->avatar) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $blocked->count(),
            'message' => 'Blocked users retrieved successfully',
            'data' => $blocked
        ]);
    }
}

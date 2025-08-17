<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class FollowerController extends Controller
{
    public function follow($userId)
    {
        $user = auth('api')->user();

        if ($user->id == $userId) {
            return response()->json(['success' => false, 'message' => "You can't follow yourself"], 400);
        }

        if ($user->followings()->where('following_id', $userId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Already following this user'], 400);
        }

        $user->followings()->attach($userId);

        return response()->json(['success' => true, 'message' => 'Followed successfully']);
    }

    public function unfollow($userId)
    {
        $user = auth('api')->user();

        $user->followings()->detach($userId);

        return response()->json(['success' => true, 'message' => 'Unfollowed successfully']);
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
}

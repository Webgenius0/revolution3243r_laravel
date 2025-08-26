<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public $select;
    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'name', 'email', 'avatar', 'otp_verified_at', 'last_activity_at', 'bio'];
    }

    public function me()
    {
        $data = User::select($this->select)->find(auth('api')->user()->id);

        $posts = $data->posts()
            ->withCount('likes')                 // Correct withCount syntax
            ->with(['media', 'comments', 'likes'])
            ->get();

        // Add is_following flag for each post
        foreach ($posts as $post) {
            $post->is_following = auth()->user()->followings()
                ->where('following_id', $post->user_id) // or $post->user->id
                ->exists();
        }
        $data = [
            'id' => $data->id,
            'name' => $data->name,
            'bio' => $data->bio,
            'avatar' => $data->avatar,
            'email' => $data->email,
            'total_post' => $data->posts->count(),
            'following' => $data->followings->count(),
            'followers' => $data->followers->count(),
        ];

        return Helper::jsonResponse(true, 'User details fetched successfully', 200, $data);
    }

  public function updateProfile(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:100',
        'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,heif,heic|max:100000', // 5MB max
        'phone' => 'nullable|string|numeric|max_digits:20',
        'password' => 'nullable|string|min:6|confirmed',
        'address' => 'nullable|string|max:255',
        'bio' => 'nullable|string',
    ]);

    $user = auth('api')->user();

    // Handle password
    if (!empty($validatedData['password'])) {
        $validatedData['password'] = bcrypt($validatedData['password']);
    } else {
        unset($validatedData['password']);
    }

    // Handle avatar upload
    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if (!empty($user->avatar)) {
            Helper::fileDelete(public_path($user->getRawOriginal('avatar')));
        }

        $file = $request->file('avatar');
        $validatedData['avatar'] = Helper::fileUpload(
            $file,
            'user/avatar',
            getFileName($file)
        );
    } else {
        // Keep old avatar if no new uploaded
        $validatedData['avatar'] = $user->avatar;
    }

    // For other nullable fields, keep existing if empty
    $fieldsToCheck = ['phone', 'address', 'bio'];
    foreach ($fieldsToCheck as $field) {
        if (!array_key_exists($field, $validatedData) || $validatedData[$field] === null || $validatedData[$field] === '') {
            $validatedData[$field] = $user->{$field};
        }
    }

    $user->update($validatedData);

    $data = User::select($this->select)->with('roles')->find($user->id);
    return Helper::jsonResponse(true, 'Profile updated successfully', 200, $data);
}



    public function updateAvatar(Request $request)
    {
        $validatedData = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);
        $user = auth('api')->user();
        if (!empty($user->avatar)) {
            Helper::fileDelete(public_path($user->getRawOriginal('avatar')));
        }
        $validatedData['avatar'] = Helper::fileUpload($request->file('avatar'), 'user/avatar', getFileName($request->file('avatar')));
        $user->update($validatedData);
        $data = User::select($this->select)->with('roles')->find($user->id);
        return Helper::jsonResponse(true, 'Avatar updated successfully', 200, $data);
    }

    public function delete()
    {
        $user = User::findOrFail(auth('api')->id());
        if (!empty($user->avatar) && file_exists(public_path($user->avatar))) {
            Helper::fileDelete(public_path($user->avatar));
        }
        Auth::logout('api');
        $user->delete();
        return Helper::jsonResponse(true, 'Profile deleted successfully', 200);
    }

    public function destroy()
    {
        $user = User::findOrFail(auth('api')->id());
        if (!empty($user->avatar) && file_exists(public_path($user->avatar))) {
            Helper::fileDelete(public_path($user->avatar));
        }
        Auth::logout('api');
        $user->forceDelete();
        return Helper::jsonResponse(true, 'Profile deleted successfully', 200);
    }
}

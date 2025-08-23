<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    // List all posts with their media
    // public function index()
    // {
    //     $authUser = Auth::user(); // always exists because of auth middleware

    //     $posts = Post::withCount(['likes', 'comments'])
    //         ->with('media', 'user')
    //         ->latest()
    //         ->get()
    //         ->filter(function ($post) use ($authUser) {
    //             // Remove posts where the author is blocked by auth user
    //             return !$authUser->blockedUsers()->where('blocked_user_id', $post->user->id)->exists();
    //         });

    //     $data = $posts->map(function ($post) use ($authUser) {

    //         $isFollowing = $authUser->followings()->where('following_id', $post->user->id)->exists();

    //         return [
    //             'id' => $post->id,
    //             'title' => $post->content,
    //             'user_name' => $post->user->name,
    //             'avatar' => $post->user->avatar ? url($post->user->avatar) : null,
    //             'user_id' => $post->user->id,
    //             'posted_on' => $post->user->created_at->format('F d, Y'),
    //             'is_following' => $isFollowing ? 'yes' : 'no',
    //             'likes_count' => $post->likes_count,
    //             'comments_count' => $post->comments_count,
    //             'media' => $post->media,
    //         ];
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Posts retrieved successfully',
    //         'data' => $data,
    //     ]);
    // }

    public function mypost(Request $request, $id = null)
    {
        // ✅ Validate type
        $request->validate([
            'type' => 'required|in:all,latest,popular',
        ], [
            'type.required' => 'The type field is required.',
            'type.in'       => 'The type must be one of: all, latest, popular.',
        ]);

        // Get authenticated user
        $authUser = auth('api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Authenticated user not found',
            ], 404);
        }

        // Get posts owner (may be another user by $id)
        $user = $id ? User::find($id) : $authUser;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $type = $request->type;

        // Base query
        $query = $user->posts()
            ->withCount(['likes', 'comments'])
            ->with(['media', 'likes']); // include likes for is_liked check

        // Apply type filters
        switch ($type) {
            case 'latest':
                $query->latest();
                break;

            case 'popular':
                $query->orderByDesc('likes_count');
                break;

            case 'all':
            default:
                // no extra order
                break;
        }

        $posts = $query->get();

        // Format posts
        $data = $posts->map(function ($post) use ($authUser) {
            return [
                'id'             => $post->id,
                'title'          => $post->content,
                'user_name'      => $post->user->name,
                'avatar'         => $post->user->avatar ? url($post->user->avatar) : null,
                'user_id'        => $post->user->id,
                'posted_on'      => $post->created_at->format('F d, Y'),
                'is_liked'       => $post->likes->contains('user_id', $authUser->id), // ✅ use authenticated user
                'likes_count'    => $post->likes_count,
                'comments_count' => $post->comments_count,
                'media'          => $post->media,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' posts retrieved successfully',
            'data'    => $data,
        ]);
    }


    public function index(Request $request, $id = null)
    {
        // ✅ Validate request
        $request->validate([
            'type' => 'required|in:all,latest,popular,following',
        ]);

        // ✅ Auth user or by ID
        $authUser = $id ? User::find($id) : auth('api')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $type = $request->get('type'); // always valid now

        // ✅ Base query
        $query = Post::withCount(['likes', 'comments'])
            ->with(['media', 'user', 'likes']); // include likes relation

        // ✅ Type filtering
        if ($type === 'latest') {
            $query->latest(); // newest first
        } elseif ($type === 'popular') {
            $query->orderByDesc('likes_count');
        } elseif ($type === 'following') {
            $followingIds = $authUser->followings()->pluck('following_id');
            $query->whereIn('user_id', $followingIds)->latest();
        } elseif ($type === 'all') {
            $query->get(); // oldest first
        }

        // ✅ Get all blocked user IDs once
        $blockedIds = $authUser->blockedUsers()->pluck('blocked_user_id')->toArray();

        // ✅ Get posts and filter blocked users
        $posts = $query->get()->filter(function ($post) use ($blockedIds) {
            return !in_array($post->user->id, $blockedIds);
        });

        // ✅ Get all following IDs once for efficiency
        $followingIds = $authUser->followings()->pluck('following_id')->toArray();

        // ✅ Format response
        $data = $posts->map(function ($post) use ($authUser, $followingIds) {
            return [
                'id'             => $post->id,
                'title'          => $post->content,
                'user_name'      => $post->user->name,
                'avatar'         => $post->user->avatar ? url($post->user->avatar) : null,
                'user_id'        => $post->user->id,
                'posted_on'      => $post->created_at->format('F d, Y'),
                'is_liked'       => $post->likes->contains('user_id', $authUser->id), // ✅ Corrected
                'is_following'   => in_array($post->user->id, $followingIds) ? 'yes' : 'no',
                'likes_count'    => $post->likes_count,
                'comments_count' => $post->comments_count,
                'media'          => $post->media,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' posts retrieved successfully',
            'data'    => $data,
        ]);
    }



    // Store a new post with optional media files
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string',
            'type' => 'string|in:text,image,video',
            'media_files.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:102400',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth('api')->user();

        $post = Post::create([
            'user_id' => $user->id,
            'content' => $request->content,
            'type' => $request->type ?? 'text',
        ]);

        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $fileName = getFileName($file);
                $filePath = Helper::fileUpload($file, 'media', $fileName);

                $type = $file->getClientMimeType(); // <-- this is what you want

                $post->media()->create([
                    'file_path' => $filePath,
                    'type' => $type,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post->load('media'),
        ], 201);
    }

    // Show a single post with its media
    public function show($id)
    {
        $post = Post::with('media')->find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        return response()->json($post);
    }

    // Update post content and media
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string',
            'type' => 'string|in:text,image,video',
            'media_files.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:102400',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth('api')->user();

        $post = Post::where('id', $id)->where('user_id', $user->id)->first();

        if (!$post) {
            return response()->json(['error' => 'Post not found or unauthorized'], 404);
        }

        // Update post fields
        $post->content = $request->content ?? $post->content;
        $post->type = $request->type ?? $post->type;
        $post->save();

        // Handle new media files if any
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $fileName = getFileName($file);
                $filePath = Helper::fileUpload($file, 'media', $fileName);

                $type = $file->getClientMimeType();

                $post->media()->create([
                    'file_path' => $filePath,
                    'type' => $type,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post->load('media'),
        ], 200);
    }


    // Delete post and related media
    public function destroy(Request $request, $id)
    {
        $post = Post::with('media')->find($id);
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        if ($request->user()->id !== $post->user_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        foreach ($post->media as $media) {
            Helper::fileDelete(public_path($media->getRawOriginal('file_path')));
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
    public function like_unlike(Request $request, $id)
    {
        $post = Post::with('media')->find($id);
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }
        $liked = PostLike::where('user_id', auth('api')->id())->where('post_id', $post->id)->first();

        if ($liked) {

            $liked->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post Unliked'
            ]);
        } else {
            $post->likes()->create([
                'user_id' => auth('api')->id(),
                'post_id' => $post->id,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Post Liked',
                'data' => $post,
            ]);
        }
    }
    public function comment(Request $request, $id)
    {
        $post = Post::with('comments')->find($id);
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }
        if ($request->parent_id) {
            $parentExists = Comment::where('id', $request->parent_id)->exists();
            if (!$parentExists) {
                return response()->json(['success' => false, 'message' => 'Parent comment does not exist'], 400);
            }
        }
        $comment = new Comment();
        $comment->user_id = auth('api')->id();
        $comment->post_id = $post->id;
        $comment->comment = $request->comment;
        $comment->parent_id = $request->parent_id ?? null; // support reply by parent_id

        $comment->save();

        return response()->json([
            'success' => true,
            'message' => 'Comment Done',
            'data' => $post->load('comments'), // reload comments to include new one
        ]);
    }

    public function allcomment(Request $request, $id)
    {
        // Only top-level comments
        $comments = Comment::where('post_id', $id)
            ->with(['replies.user', 'user'])
            ->whereNull('parent_id')        // <-- Only main comments

            ->get();

        $comments = $comments->map(function ($comment) {
            return [
                'id'         => $comment->id,
                'post_id'    => $comment->post_id,
                'comment'    => $comment->comment,
                'user_id'    => $comment->user_id,
                'user_name'  => $comment->user->name ?? null,
                'avatar'     => $comment->user->avatar ? url($comment->user->avatar) : null,
                'created_at' => $comment->created_at->diffForHumans(),
                'updated_at' => $comment->updated_at->diffForHumans(),
                'replies'    => $comment->replies->map(function ($repl) {
                    return [
                        'id'         => $repl->id,
                        'post_id'    => $repl->post_id,
                        'comment'    => $repl->comment,
                        'parent_id'  => $repl->parent_id,
                        'user_id'    => $repl->user_id,
                        'user_name'  => $repl->user->name ?? null,
                        'avatar'     => $repl->user->avatar ? url($repl->user->avatar) : null,
                        'created_at' => $repl->created_at->diffForHumans(),
                        'updated_at' => $repl->updated_at->diffForHumans(),
                    ];
                }),
            ];
        });

        if ($comments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No comments found for this post'
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comments retrieved successfully',
            'data'    => $comments,
        ], 200);
    }


    public function updateComment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        if ($comment->user_id !== auth('api')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $comment->comment = $request->comment;
        $comment->save();

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $comment,
        ]);
    }

    // Delete a comment
    public function deleteComment(Request $request, $id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        if ($comment->user_id !== auth('api')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }

    public function wholikes($id)
    {
        $post = Post::with('likes.user')->find($id);  // eager load user for likes

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        // Get all user names who liked this post
        $data = $post->likes->map(function ($like) {
            return [
                'id'     => $like->user->id,
                'name'   => $like->user->name,
                'avatar' => $like->user->avatar ? url($like->user->avatar) : null, // ✅ fixed
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Likes retrieved successfully',
            'data' => $data,
        ]);
    }
}

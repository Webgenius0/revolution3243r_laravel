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
    public function index()
    {
        $authUser = Auth::user(); // always exists because of auth middleware

        $posts = Post::withCount(['likes', 'comments'])
            ->with('media', 'user')
            ->latest()
            ->get()
            ->filter(function ($post) use ($authUser) {
                // Remove posts where the author is blocked by auth user
                return !$authUser->blockedUsers()->where('blocked_user_id', $post->user->id)->exists();
            });

        $data = $posts->map(function ($post) use ($authUser) {

            $isFollowing = $authUser->followings()->where('following_id', $post->user->id)->exists();

            return [
                'id' => $post->id,
                'title' => $post->content,
                'user_name' => $post->user->name,
                'user_id' => $post->user->id,
                'posted_on' => $post->user->created_at->format('F d, Y'),
                'is_following' => $isFollowing ? 'yes' : 'no',
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
                'media' => $post->media,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Posts retrieved successfully',
            'data' => $data,
        ]);
    }

    public function mypost($id = null)
    {
        $user = $id ? User::find($id) : auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Get posts of the user with counts and media
        $posts = $user->posts()
            ->withCount(['likes', 'comments'])
            ->with('media')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Posts retrieved successfully',
            'data' => $posts
        ]);
    }
    public function latestpost($id = null)
    {
        $user = $id ? User::find($id) : auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Get posts of the user with counts and media
        $posts = $user->posts()
            ->withCount(['likes', 'comments'])
            ->with('media')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Posts retrieved successfully',
            'data' => $posts
        ]);
    }
    public function popularpost($id = null)
    {
        $user = $id ? User::find($id) : auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Get posts of the user with counts and media
        $posts = $user->posts()
            ->withCount(['likes', 'comments'])
            ->with('media')
            ->orderByDesc('likes_count') // order by number of likes
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Posts retrieved successfully',
            'data' => $posts
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
        $comments = Comment::where('post_id', $id)
            ->with('replies')              // recursively load replies
            ->get();
        if ($comments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No comments found for this post'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comments retrieved successfully',
            'data' => $comments,
        ]);
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
                'id' => $like->user->id,
                'name' => $like->user->name
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Likes retrieved successfully',
            'data' => $data,
        ]);
    }
}

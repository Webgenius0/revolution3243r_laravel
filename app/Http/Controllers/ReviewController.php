<?php

namespace App\Http\Controllers;

use App\Events\ReviewEvent;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewNotification;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // List all reviews
    public function index()
    {
        $reviews = Review::all();
        return response()->json($reviews, 200);
    }

    // Show single review
    public function show($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json($review, 200);
    }

    // Create a new review
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'message' => 'required|string',
            'type'    => 'required|string|in:high,medium,low',
        ]);

        $user = auth('api')->user(); // authenticated API user

        // Create new review
        $review = new Review();
        $review->user_id = $user->id;
        $review->email   = $user->email;
        $review->message = $validated['message'];
        $review->type    = $validated['type'];
        $review->save();

        // Notify all admins
        $admin = User::where('email', 'admin@admin.com')->first();

        if ($admin) {
            $admin->notify(new ReviewNotification($review));
        }
        broadcast(New ReviewEvent($review));
        return response()->json([
            'success' => true,
            'message' => 'Review submitted and admins notified.',
            'data' => [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'email' => $review->email,
                'message' => $review->message,
                'type' => $review->type,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ]
        ]);
    }

    // Update a review
    public function update(Request $request, $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        if ($review->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'sometimes|string',
            'type'    => 'sometimes|string|in:medal,high,low',
        ]);

        if ($request->has('message')) {
            $review->message = $request->message;
        }
        if ($request->has('type')) {
            $review->type = $request->type;
        }

        $review->save(); // update

        return response()->json([
            'message' => 'Review updated successfully',
            'data' => $review
        ], 200);
    }

    // Delete a review
    public function destroy($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        if ($review->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully'], 200);
    }
}

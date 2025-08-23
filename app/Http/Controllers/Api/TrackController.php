<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Track;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function index()
    {
        $tracks = Track::with('images')->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Tracks retrieved successfully',
            'data'    => $tracks
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'start_name' => 'nullable|string|max:255',
            'start_lat'   => 'nullable|string|max:255',
            'start_lng'   => 'nullable|string|max:255',
            'end_lat' => 'nullable|string|max:255',
            'end_lng'   => 'nullable|string|max:255',
            'end_name'   => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'images.*'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $track = Track::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $fileName = getFileName($file);
                $filePath = Helper::fileUpload($file, 'tracks', $fileName);

                $type = $file->getClientMimeType();

                $track->images()->create([
                    'image' => $filePath,
                    'track_id' => $track->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Track created successfully',
            'data'    => $track->load('images'),
        ], 201);
    }

    public function show($id)
    {
        try {
            $track = Track::with('images')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Track retrieved successfully',
                'data'    => $track,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Track not found',
            ], 200);
        }
    }


  public function update(Request $request, $id)
{
    $track = Track::findOrFail($id);

    $validated = $request->validate([
        'title'          => 'required|string|max:255',
        'start_location' => 'nullable|string|max:255',
        'end_location'   => 'nullable|string|max:255',
        'description'    => 'nullable|string',
        'images.*'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'delete_images'  => 'nullable|array',        // Array of image IDs to delete
        'delete_images.*'=> 'integer|exists:track_images,id',
    ]);

    // ✅ Update main track data
    $track->update($validated);

    // ✅ Handle image deletion
    if ($request->filled('delete_images')) {
        foreach ($request->delete_images as $imageId) {
            $image = $track->images()->find($imageId);
            if ($image) {
                // delete file from storage
                if (file_exists(public_path($image->image))) {
                    unlink(public_path($image->image));
                }
                $image->delete();
            }
        }
    }

    // ✅ Handle new image uploads
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $file) {
            $fileName = getFileName($file);
            $filePath = Helper::fileUpload($file, 'tracks', $fileName);

            $track->images()->create([
                'image'    => $filePath,
                'track_id' => $track->id,
            ]);
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Track updated successfully',
        'data'    => $track->load('images'),
    ]);
}


public function destroy($id)
{
    $track = Track::with('images')->find($id);

    if (!$track) {
        return response()->json([
            'success' => false,
            'message' => 'Track not found',
        ], 200); // ✅ Still return 200
    }

    // ✅ Delete all related images from storage
    foreach ($track->images as $image) {
        Helper::fileDelete(public_path($image->image));
        $image->delete();
    }

    // ✅ Delete the track itself
    $track->delete();

    return response()->json([
        'success' => true,
        'message' => 'Track deleted successfully',
    ], 200);
}


}

<?php

namespace App\Http\Controllers\Api\Frontend;

use Exception;
use App\Helpers\Helper;
use App\Models\Challenge;
use Illuminate\Http\Request;
use App\Models\ChallengeMedia;
use App\Helpers\DistanceHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ChallengeParticipant;
use App\Models\ChallengeRequirement;
use App\Http\Requests\ChallengeRequest;
use App\Http\Resources\ChallengeResource;

class ChallengeController extends Controller
{
    /**
     * Store challenge
     */
    public function store(ChallengeRequest $request): JsonResponse
    {
        try {
            $validated_data = $request->validated();
            $validated_data['user_id'] = auth('api')->id();

            DB::beginTransaction();

            // Save Challenge
            $challenge = Challenge::create($validated_data);

            // Save Requirements
            if ($request->filled('requirements')) {
                foreach ($request->requirements as $req) {
                    ChallengeRequirement::create([
                        'challenge_id'     => $challenge->id,
                        'requirement_type' => $req,
                    ]);
                }
            }

            // Save Media
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = Helper::fileUpload($image, 'challenges', $challenge->title);

                    if ($path) {
                        ChallengeMedia::create([
                            'challenge_id' => $challenge->id,
                            'image_path'   => $path,
                        ]);
                    }
                }
            }

            DB::commit();

            return Helper::jsonResponse(
                true,
                'Challenge created successfully',
                200,
                new ChallengeResource($challenge->load(['requirements', 'media']))
            );
        } catch (Exception $e) {
            DB::rollBack();
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }

    /**
     * Show all challenges with pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);

            $challenges = Challenge::with(['requirements', 'media', 'user'])
                ->withCount('participants')
                ->latest('id')
                ->paginate($perPage);

            // Format response with pagination
            $responseData = [
                'challenges' => ChallengeResource::collection($challenges),
                'meta' => [
                    'current_page' => $challenges->currentPage(),
                    'last_page'    => $challenges->lastPage(),
                    'per_page'     => $challenges->perPage(),
                    'total'        => $challenges->total(),
                ],
            ];

            return Helper::jsonResponse(
                true,
                'Challenges fetched successfully',
                200,
                $responseData
            );
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }


    /**
     * Show only authenticated user's challenges with pagination
     */
    public function myChallenges(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return Helper::jsonResponse(false, 'Unauthorized user.', 401);
            }

            $perPage = $request->input('per_page', 10);

            $challenges = Challenge::with(['requirements', 'media'])
                ->where('user_id', $user->id)
                ->latest('id')
                ->paginate($perPage);

            // Format response with pagination
            $responseData = [
                'challenges' => ChallengeResource::collection($challenges),
                'meta' => [
                    'current_page' => $challenges->currentPage(),
                    'last_page'    => $challenges->lastPage(),
                    'per_page'     => $challenges->perPage(),
                    'total'        => $challenges->total(),
                ],
            ];

            return Helper::jsonResponse(
                true,
                'Challenges fetched successfully',
                200,
                $responseData
            );
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }


    /**
     * Edit challenge
     */
    public function edit($id): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return Helper::jsonResponse(false, 'Unauthorized user', 401);
            }

            // Sudhu ei user er challenge fetch korbe
            $challenge = Challenge::with(['requirements', 'media', 'user'])
                ->where('user_id', $user->id)
                ->findOrFail($id);

            return Helper::jsonResponse(
                true,
                'Challenge fetched successfully',
                200,
                new ChallengeResource($challenge)
            );
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Challenge not found or not owned by you', 404);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }


    /**
     * Update challege
     */
    public function update(ChallengeRequest $request, $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return Helper::jsonResponse(false, 'Unauthorized user', 401);
            }

            $validated = $request->validated();

            // Find challenge owned by this user
            $challenge = Challenge::where('user_id', $user->id)
                ->findOrFail($id);

            DB::beginTransaction();

            // Update basic fields
            $challenge->update($request->only([
                'title',
                'challenge_type',
                'description',
                'start_date',
                'end_date',
                'start_location',
                'end_location'
            ]));

            // Update Requirements
            if ($request->filled('requirements')) {
                $challenge->requirements()->delete();
                foreach ($request->requirements as $req) {
                    ChallengeRequirement::create([
                        'challenge_id'     => $challenge->id,
                        'requirement_type' => $req,
                    ]);
                }
            }

            // Update Media
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = Helper::fileUpload($image, 'challenges', $challenge->title);
                    if ($path) {
                        ChallengeMedia::create([
                            'challenge_id' => $challenge->id,
                            'image_path'   => $path,
                        ]);
                    }
                }
            }

            DB::commit();

            return Helper::jsonResponse(
                true,
                'Challenge updated successfully',
                200,
                new ChallengeResource($challenge->load(['requirements', 'media', 'user']))
            );
        } catch (Exception $e) {
            DB::rollBack();
            return Helper::jsonResponse(false, 'Challenge not found or not owned by you', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }

    /**
     * Delete challenge
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return Helper::jsonResponse(false, 'Unauthorized user', 401);
            }

            // Fetch challenge owned by this user
            $challenge = Challenge::where('user_id', $user->id)->findOrFail($id);

            DB::beginTransaction();

            // Delete media files from disk
            foreach ($challenge->media as $media) {
                if (!empty($media->image_path)) {
                    Helper::fileDelete(public_path($media->image_path));
                }
            }

            // Delete media and requirements from DB
            $challenge->media()->delete();
            $challenge->requirements()->delete();

            // Delete the challenge itself
            $challenge->delete();

            DB::commit();

            return Helper::jsonResponse(true, 'Challenge deleted successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return Helper::jsonResponse(false, 'Challenge not found or not owned by you', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }


    /*
    *Join challenge
    */
    public function join($challenge_id): JsonResponse
    {
        try {
            $authUser = auth('api')->user();
            if (!$authUser) {
                return Helper::jsonResponse(false, 'Unauthorized', 401);
            }

            $challenge = Challenge::findOrFail($challenge_id);

            // Check if user already joined
            $alreadyJoined = ChallengeParticipant::where('challenge_id', $challenge->id)
                ->where('user_id', $authUser->id)
                ->exists();

            if ($alreadyJoined) {
                return Helper::jsonResponse(false, 'You have already joined this challenge', 400);
            }

            // Create participant record
            $participant = ChallengeParticipant::create([
                'challenge_id' => $challenge->id,
                'user_id'      => $authUser->id,
                'joined_at'    => now(),
            ]);

            return Helper::jsonResponse(true, 'Successfully joined the challenge', 200, $participant);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Challenge not found', 404);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }


    /*
    * Get challenge details
    */
    public function show($id): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return Helper::jsonResponse(false, 'Unauthorized user', 401);
            }

            // Challenge fetch with participants count
            $challenge = Challenge::with(['requirements', 'media', 'user'])
                ->withCount('participants')
                ->findOrFail($id);

            // Distance calculate (if lat/long exists)
            $distance = null;
            if (
                $challenge->start_latitude && $challenge->start_longitude &&
                $challenge->end_latitude && $challenge->end_longitude
            ) {
                $distance = DistanceHelper::calculateDistance(
                    $challenge->start_latitude,
                    $challenge->start_longitude,
                    $challenge->end_latitude,
                    $challenge->end_longitude
                );
            }

            // Resource data with extra distance
            $data = new ChallengeResource($challenge);
            $data->additional(['distance_km' => $distance]);

            return Helper::jsonResponse(
                true,
                'Challenge fetched successfully',
                200,
                $data
            );
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Challenge not found', 404);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }
}

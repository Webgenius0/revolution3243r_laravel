<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\DistanceHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {

        $now = Carbon::now();

        // Duration in days remaining
        $remainingDays = null;
        if ($this->end_date) {
            $endDate = Carbon::parse($this->end_date);
            $remainingDays = $now->diffInDays($endDate, false);
        }

        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'challenge_type' => $this->challenge_type,
            'description'    => $this->description,
            'start_date'     => Carbon::parse($this->start_date)->format('d M Y, h:i A'),
            'end_date'       => Carbon::parse($this->end_date)->format('d M Y, h:i A'),
            'start_location' => $this->start_location,
            'end_location'   => $this->end_location,
            'user_id'        => $this->user_id,

            // Requirements
            'requirements'   => ChallengeRequirementResource::collection($this->whenLoaded('requirements')),

            // Media
            'media'          => ChallengeMediaResource::collection($this->whenLoaded('media')),

            //perticipent
            'participants_count' => $this->participants_count,

            // calculated distance
            'distance_km' => DistanceHelper::calculateDistance(
                $this->start_latitude,
                $this->start_longitude,
                $this->end_latitude,
                $this->end_longitude
            ),

            // Remaining days
            'duration_left_days' => (int) $remainingDays > 0 ? (int) $remainingDays : 0,

            'created_at'     => $this->created_at->format('d M Y H:i'),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id'     => $this->user->id,
                    'name'   => $this->user->name,
                    'avatar' => asset($this->user->avatar)
                ];
            }),
        ];
    }


    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return null;
        }

        $earthRadius = 6371; // KM

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}

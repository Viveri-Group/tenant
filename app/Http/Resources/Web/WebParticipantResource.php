<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['organisation']);

        return [
            'id' => $this->id,
            'call_id' => $this->call_id,
            'competition_id' => $this->competition_id,
            'competition_phone_line' => new WebCompetitionPhoneLineResource($this->phoneLine),
            'competition_phone_number' => $this->competition_phone_number,
            'is_free_entry' => $this->is_free_entry,
            'telephone' => $this->telephone,
            'organisation_id' => $this->organisation->id,
            'organisation_name' => $this->organisation->name,
            'drawn_at' => $this->drawn_at,
            'call_start' => $this->call_start,
        ];
    }
}

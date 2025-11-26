<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebOrphanActiveCallsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['organisation']);

        return [
            'id' => $this->id,
            'competition_id' => $this->competition_id,
            'call_id' => $this->call_id,
            'phone_number' => $this->phone_number,
            'caller_phone_number' => $this->caller_phone_number,
            'organisation_id' => $this->organisation->id,
            'organisation_name' => $this->organisation->name,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'original_call_time' => $this->original_call_time,
        ];
    }
}

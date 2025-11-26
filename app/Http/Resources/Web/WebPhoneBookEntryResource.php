<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebPhoneBookEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['organisation']);

        return [
            'type' => 'phone-book-entry',
            'id' => $this->id,
            'attributes' => [
                'phone_number' => $this->phone_number,
                'name' => $this->name,
                'organisation_id' => $this->organisation->id,
                'organisation_name' => $this->organisation->name
            ]
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneBookEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'phone-book-entry',
            'id' => $this->id,
            'attributes' => [
                'phone_number' => $this->phone_number,
                'name' => $this->name,
            ]
        ];
    }
}

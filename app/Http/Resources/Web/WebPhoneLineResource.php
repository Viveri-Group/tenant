<?php

namespace App\Http\Resources\Web;

use App\Http\Resources\FileUploadResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebPhoneLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['files']);

        return [
            'id' => $this->id,
            'attributes' => [
                'number' => $this->phone_number,
                'files' => FileUploadResource::collection($this->files),
            ]
        ];
    }
}

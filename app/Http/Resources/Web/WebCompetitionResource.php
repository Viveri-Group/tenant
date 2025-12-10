<?php

namespace App\Http\Resources\Web;

use App\Action\File\GetCompetitionDefaultAudioAction;
use App\Enums\CompetitionAudioType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebCompetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['phoneLines', 'files', 'organisation']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_open' => $this->isOpen,
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),

            'organisation' => new WebOrganisationResource($this->organisation),
//            'type' => $this->type,
            'special_offer' => $this->special_offer,
            'max_entries' => $this->max_entries,
            'created_at' => $this->created_at,
            'phone_lines' => WebPhoneLineResource::collection($this->phoneLines),
            'files' => WebFileUploadResource::collection($this->files),
            'default_audio' => (new GetCompetitionDefaultAudioAction($this->organisation_id, CompetitionAudioType::names()))->handle()
        ];
    }
}

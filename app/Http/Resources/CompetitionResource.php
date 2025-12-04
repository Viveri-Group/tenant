<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['phoneLines', 'winners', 'files', 'draws', 'organisation']);

        return [
            'type' => 'competition',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'is_open' => $this->isOpen,
                'start' => $this->start->toIso8601String(),
                'end' => $this->end->toIso8601String(),

//                'active_from' => $this->type !== 'WHOLE_COMPETITION' ? $this->active_from : null,
//                'active_to' => $this->type !== 'WHOLE_COMPETITION' ? $this->active_to : null,
//                'day_of_week' => $this->type === 'WEEKLY' ? $this->day_of_week : null,

//                'type' => $this->type,
                'max_entries' => $this->max_entries,

//                'promo_code_id' => $this->promo_code_id,
//                'promo_code_id_first_entry' => $this->promo_code_id_first_entry,
                'sms_mask' => $this->sms_mask ?? config('services.dmb-uk.sms.mask'),
                'sms_offer_enabled' => $this->sms_offer_enabled ?? false,
                'sms_offer_message' => $this->sms_offer_message,
                'sms_first_entry_enabled' => $this->sms_first_entry_enabled ?? false,
                'sms_first_entry_message' => $this->sms_first_entry_message,
            ],
            'relationships' => [
                'organisation' => OrganisationResource::make($this->organisation),
                'phone_lines' => PhoneLineChildResource::collection($this->phoneLines),
                'winner' => WinnerResource::collection($this->winners),
                'files' => FileUploadResource::collection($this->files),
                'draws' => CompetitionDrawsCollection::collection($this->draws)
            ]
        ];
    }
}

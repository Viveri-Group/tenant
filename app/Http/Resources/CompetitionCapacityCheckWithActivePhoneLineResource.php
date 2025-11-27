<?php

namespace App\Http\Resources;

use App\Action\File\GetCompetitionAudioAction;
use App\Enums\CompetitionAudioType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionCapacityCheckWithActivePhoneLineResource extends JsonResource
{
    public function __construct($resource, public array $parameters = [])
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $audio = (new GetCompetitionAudioAction(CompetitionAudioType::names()))->handle($this->resource);

        return [
            'competition_id' => $this->resource?->competition_id,
            'status' => $this->parameters['status'],
            'active_call_id' => $this->parameters['active_call_id'],
            'total_entry_count' => $this->parameters['entry_count']['total_entry_count'] ?? 0,
            'entries_warning' => $this->resource?->competition?->entries_warning ?? 0,
            'max_paid_entries' => $this->resource?->competition?->max_paid_entries,
            'special_offer' => $this->resource?->competition?->special_offer ?? 'FALSE',
            ...$audio,
        ];
    }
}

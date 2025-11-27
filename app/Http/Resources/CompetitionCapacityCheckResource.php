<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionCapacityCheckResource extends JsonResource
{
    public function __construct($resource, public array $parameters = [])
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'competition_id' => $this->resource ? $this->resource->id : $this->parameters['competition_id'],
            'status' => $this->parameters['status'],
            'active_call_id' => $this->parameters['active_call_id'],
            'total_entry_count' => $this->parameters['entry_count']['total_entry_count'] ?? 0,
            'entries_warning' => $this->resource?->entries_warning ?? 0,
            'max_paid_entries' => $this->resource?->max_paid_entries,
            'special_offer' => $this->resource?->special_offer ?? 'FALSE',
        ];
    }
}

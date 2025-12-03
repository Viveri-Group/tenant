<?php

namespace App\Action\Competition;

use App\Action\PhoneBook\PhoneBookLookupAction;
use App\DTO\ActiveCall\ActiveCallDTO;
use App\Models\FailedEntry;

class CompetitionClearDownFailAction
{
    public function handle(ActiveCallDTO $activeCallDTO, string $reason): void
    {
        $phoneBookEntry = (new PhoneBookLookupAction())->handle($activeCallDTO->competition_phone_number);

        FailedEntry::create([
            'organisation_id' => $activeCallDTO->organisation_id,
            'competition_id' => $activeCallDTO->competition_id,
            'call_id' => $activeCallDTO->call_id,
            'phone_number' => $activeCallDTO->competition_phone_number,
            'caller_phone_number' => $activeCallDTO->caller_phone_number,
            'reason' => $reason,
            'call_start' => $activeCallDTO->created_at,
            'call_end' => $activeCallDTO->call_end,
            'round_start' => $activeCallDTO->round_start,
            'round_end' => $activeCallDTO->round_end,
            'station_name' => $phoneBookEntry?->name,
        ]);
    }
}

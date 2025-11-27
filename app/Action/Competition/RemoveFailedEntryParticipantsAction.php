<?php

namespace App\Action\Competition;

use App\DTO\ActiveCall\ActiveCallDTO;
use App\Models\EntrantRoundCount;
use App\Models\Participant;

class RemoveFailedEntryParticipantsAction
{
    public function handle(ActiveCallDTO $activeCallDTO): void
    {
        $participantTotalCount = Participant::where('call_id', $activeCallDTO->call_id)->count();

        $paidCount = Participant::where('call_id', $activeCallDTO->call_id)
            ->where('is_free_entry', false)
            ->count();

        Participant::where('call_id', $activeCallDTO->call_id)->delete();

        $roundCount = EntrantRoundCount::where('hash', hash('xxh128', "{$activeCallDTO->competition_id} {$activeCallDTO->caller_phone_number}"))->first();

        $roundCount?->update([
            'total_entry_count' => max(0, (int)$roundCount->total_entry_count - $participantTotalCount),
        ]);
    }
}

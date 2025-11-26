<?php

namespace App\Action\Competition;

use App\DTO\ActiveCall\ActiveCallDTO;
use App\Models\EntrantRoundCount;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;

class LogEntrantRoundCountAction
{
    public function handle(
        ActiveCallDTO $activeCallDTO,
        Participant $participant,
        bool $addFreeEntry = false
    ): void
    {
        $update = [
            'total_entry_count' => DB::raw('total_entry_count + 1'),
        ];

        if ($addFreeEntry) {
            $freeParticipant = $participant->replicate();
            $freeParticipant->is_free_entry = true;
            $freeParticipant->save();

            $update['total_entry_count'] = DB::raw('total_entry_count + 2');
        }

        EntrantRoundCount::updateOrCreate(
            [
                'hash' => hash('xxh128', "{$activeCallDTO->competition_id} {$activeCallDTO->caller_phone_number}"),
                'competition_id' => $activeCallDTO->competition_id,
                'caller_number' => $activeCallDTO->caller_phone_number,
            ],
            $update
        );
    }
}

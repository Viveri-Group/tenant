<?php

namespace App\Action\EntrantRoundCount;

use App\DTO\ActiveCall\ActiveCallDTO;
use App\Models\ActiveCall;
use App\Models\EntrantRoundCount;

class GetEntrantRoundCountAction
{
    public function handle(ActiveCall|ActiveCallDTO $activeCall): array
    {
        $entrant = EntrantRoundCount::where('hash', hash('xxh128', "{$activeCall->competition_id} {$activeCall->caller_phone_number}"))->first();

        $data = [
            'total_entry_count' => 0,
        ];

        if($entrant){
            $data['total_entry_count'] = $entrant->total_entry_count;
        }

        return $data;
    }
}

<?php

namespace App\Action\Competition;

use App\Action\PhoneBook\PhoneBookLookupAction;
use App\DTO\ActiveCall\ActiveCallDTO;
use App\Jobs\SMSFirstEntryJob;
use App\Jobs\SMSOfferAcceptedJob;
use App\Models\EntrantRoundCount;
use App\Models\Participant;
use DB;

class CompetitionClearDownSuccessAction
{
    public function handle(ActiveCallDTO $activeCallDTO, bool $smsOfferAccepted): void
    {
        $phoneBookEntry = (new PhoneBookLookupAction())->handle($activeCallDTO->competition_phone_number);

        $participant = Participant::create([
            'call_id' => $activeCallDTO->call_id,
            'call_start' => $activeCallDTO->created_at,
            'call_end' => $activeCallDTO->call_end,
            'competition_id' => $activeCallDTO->competition_id,
            'competition_phone_line_id' => $activeCallDTO->competition_phone_line_id,
            'competition_phone_number' => $activeCallDTO->competition_phone_number,
            'telephone' => $activeCallDTO->caller_phone_number,
            'sms_offer_accepted' => $smsOfferAccepted,
            'round_start' => $activeCallDTO->round_start,
            'round_end' => $activeCallDTO->round_end,
            'station_name' => $phoneBookEntry?->name,
        ]);

        $entrantRoundCount = EntrantRoundCount::updateOrCreate(
            [
                'hash' => hash('xxh128', "{$activeCallDTO->round_start} {$activeCallDTO->competition_id} {$activeCallDTO->caller_phone_number}"),
                'competition_id' => $activeCallDTO->competition_id,
                'caller_number' => $activeCallDTO->caller_phone_number,
            ],
            [
                'entry_count' => DB::raw('entry_count + 1')
            ]
        );

//        if($smsOfferAccepted){
//            SMSOfferAcceptedJob::dispatch(
//                $activeCallDTO->competition_id,
//                $activeCallDTO->caller_phone_number
//            );
//        }

        $entrantRoundCount->refresh();

//        if($entrantRoundCount->entry_count === 1){
//            $participant->update([ 'sms_first_entry_sent' => true ]);

//            SMSFirstEntryJob::dispatch(
//                $activeCallDTO->competition_id,
//                $activeCallDTO->caller_phone_number
//            );
//        }
    }
}

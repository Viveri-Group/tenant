<?php

namespace App\Action;

use App\Models\Competition;
use App\Models\CompetitionWinnerAlt;
use App\Models\Participant;
use Illuminate\Support\Carbon;

class SetAltWinnerAction
{
    public function __construct(
        public Competition $competition,
        public int $position,
        public string $dateFrom,
        public string $dateTo,
    )
    {
    }

    public function handle()
    {
        $from = Carbon::parse($this->dateFrom);
        $to = Carbon::parse($this->dateTo);

        $participant = Participant::query()
            ->where('competition_id', $this->competition->id)
            ->whereBetween('call_start', [$from, $to])
            ->orderBy('id')
            ->skip($this->position - 1)
            ->take(1)
            ->first();

        if(!$participant){
            return false;
        }

        $numberOfEntries = Participant::query()
            ->where('competition_id', $this->competition->id)
            ->whereBetween('call_start', [$from, $to])
            ->where('telephone',  $participant->telephone)
            ->count();

        return CompetitionWinnerAlt::create([
            'participant_id' => $participant->id,
            'competition_id' => $participant->competition_id,
            'call_id' => $participant->call_id,
            'number_of_entries' => $numberOfEntries,
            'date_from' => Carbon::parse($from)->format('Y-m-d H:i:s'),
            'date_to' => Carbon::parse($to)->format('Y-m-d H:i:s'),
            'competition_phone_number' => $participant->competition_phone_number,
            'telephone' => $participant->telephone,
            'call_start' => $participant->call_start,
            'call_end' => $participant->call_end,
        ]);
    }
}

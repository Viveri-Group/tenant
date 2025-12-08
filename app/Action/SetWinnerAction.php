<?php

namespace App\Action;

use App\Models\CompetitionDraw;
use App\Models\Participant;
use App\Models\CompetitionWinner;

class SetWinnerAction
{
    public function __construct(public CompetitionDraw $competitionDraw, public int $position)
    {
    }

    public function handle()
    {
        $participant = Participant::query()
            ->where('competition_draw_id', $this->competitionDraw->id)
            ->orderBy('id')
            ->skip($this->position - 1)
            ->take(1)
            ->first();

        if(!$participant){
            return false;
        }

        $numberOfEntries = Participant::query()
            ->where('competition_draw_id', $this->competitionDraw->id)
            ->where('telephone',  $participant->telephone)
            ->count();

        return CompetitionWinner::create([
            'participant_id' => $participant->id,
            'competition_id' => $participant->competition_id,
            'call_id' => $participant->call_id,
            'round_hash' => $this->competitionDraw->round_hash,
            'phone_line_id' => $participant->competition_phone_line_id,
            'competition_phone_number' => $participant->phoneLine->phone_number,
            'telephone' => $participant->telephone,
            'call_start' => $participant->call_start,
            'call_end' => $participant->call_end,
            'number_of_entries' => $numberOfEntries,
        ]);
    }
}

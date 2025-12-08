<?php

namespace App\Http\Controllers;

use App\Http\Resources\WinnerResource;
use App\Models\Competition;
use App\Models\CompetitionWinner;

class GetCompetitionWinnerController extends Controller
{
    public function __invoke(Competition $competition)
    {
        $competitionWinner = CompetitionWinner::where('competition_id', $competition->id)
            ->firstOrFail();

        return new WinnerResource($competitionWinner);
    }
}

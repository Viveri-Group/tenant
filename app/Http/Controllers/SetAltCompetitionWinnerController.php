<?php

namespace App\Http\Controllers;

use App\Action\SetAltWinnerAction;
use App\Http\Requests\SetAltCompetitionWinnerRequest;
use App\Http\Resources\WinnerAltResource;
use App\Models\Competition;
use App\Models\CompetitionWinnerAlt;

class SetAltCompetitionWinnerController extends Controller
{
    public function __invoke(SetAltCompetitionWinnerRequest $request, Competition $competition)
    {
        $winner = (new SetAltWinnerAction(
            $competition,
            $request->input('position'),
            $request->input('date_from'),
            $request->input('date_to'),
        ))->handle();

        abort_if(!$winner instanceof CompetitionWinnerAlt, 403, 'Out of bounds exception - unknown participant at this index.');

        return new WinnerAltResource($winner);
    }
}

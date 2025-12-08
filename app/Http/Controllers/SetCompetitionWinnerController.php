<?php

namespace App\Http\Controllers;

use App\Action\SetWinnerAction;
use App\Http\Requests\SetCompetitionWinnerRequest;
use App\Http\Resources\WinnerResource;
use App\Models\CompetitionDraw;
use App\Models\CompetitionWinner;

class SetCompetitionWinnerController extends Controller
{
    public function __invoke(SetCompetitionWinnerRequest $request)
    {
        $competitionDraw = CompetitionDraw::query()
            ->where('round_hash', $request->input('round_hash'))
            ->first();

        abort_if($competitionDraw === null, 401, 'Round hash does not exist');

        $winner = (new SetWinnerAction($competitionDraw, $request->input('position')))->handle();

        abort_if(!$winner instanceof CompetitionWinner, 403, 'Out of bounds exception - unknown participant at this index.');

        return new WinnerResource($winner);
    }
}

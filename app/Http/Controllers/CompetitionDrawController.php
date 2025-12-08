<?php

namespace App\Http\Controllers;

use App\Action\HandleWholeCompetitionDrawAction;
use App\DTO\CompetitionDraw\CompetitionDrawFailedDTO;
use App\Http\Requests\MarkAsDrawnRequest;
use App\Models\Competition;
use Illuminate\Http\Request;

class CompetitionDrawController extends Controller
{
    public function __invoke(MarkAsDrawnRequest $request, Competition $competition)
    {
        $drawResponse = $this->handleDraw($request, $competition);

        abort_if($drawResponse instanceof CompetitionDrawFailedDTO, $drawResponse->code, $drawResponse->message);

        return response()->noContent();
    }

    private function handleDraw(Request $request, Competition $competition)
    {
        $drawnBy = $request->input('drawn_by');

        (new HandleWholeCompetitionDrawAction($competition, $drawnBy))->handle();

//        //todo return draw hash / from & to in the data
//        return match ($competition->type) {
//            CompetitionDateType::WHOLE_COMPETITION->value => (new HandleWholeCompetitionDrawAction($competition, $drawnBy))->handle(),
//            CompetitionDateType::WEEKLY->value => (new HandleCompetitionDrawAction($competition, $drawnBy))->handle((new GetRoundsInWeeks($competition))->handle()),
//            CompetitionDateType::EVERYDAY->value => (new HandleCompetitionDrawAction($competition, $drawnBy))->handle((new GetRoundsInEveryDay($competition))->handle()),
//            CompetitionDateType::DAILY->value => (new HandleCompetitionDrawAction($competition, $drawnBy))->handle((new GetRoundsInDaily($competition))->handle()),
//        };
    }
}

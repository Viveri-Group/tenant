<?php

namespace App\Action;

use App\DTO\CompetitionDraw\CompetitionDrawFailedDTO;
use App\DTO\CompetitionDraw\CompetitionDrawSuccessDTO;

class HandleWholeCompetitionDrawAction extends AbstractHandleCompetitionDraw
{
    public function handle(): CompetitionDrawFailedDTO|CompetitionDrawSuccessDTO
    {
        if ($this->competitionHasBeenDrawn(['from' => $this->competition->start->format('Y-m-d'), 'to' => $this->competition->end->format('Y-m-d')])) {
            return new CompetitionDrawFailedDTO('competition already drawn', 400);
        }

        if (now() < $this->competition->end) {
            return new CompetitionDrawFailedDTO('competition end date not met', 401);
        }

        $competitionDraw = $this->createDraw(['from' => $this->competition->start, 'to' => $this->competition->end]);

        $this->markParticipantsAsDrawn($competitionDraw, [$this->competition->start, $this->competition->end]);

        return new CompetitionDrawSuccessDTO(
            'competition drawn',
            200,
            []
        );
    }
}

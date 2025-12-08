<?php

namespace App\Action;

use App\Enums\QueuePriority;
use App\Jobs\MarkParticipantsAsDrawnJob;
use App\Models\Competition;
use App\Models\CompetitionDraw;
use Illuminate\Support\Carbon;

abstract class AbstractHandleCompetitionDraw
{
    public function __construct(public Competition $competition, public $drawnBy = null)
    {
    }

    protected function markParticipantsAsDrawn(CompetitionDraw $competitionDraw, array $dates): void
    {
        MarkParticipantsAsDrawnJob::dispatch($this->competition, $this->drawnBy, $competitionDraw, $dates)
            ->onQueue(QueuePriority::High->value);
    }

    protected function competitionHasBeenDrawn(array $dates): bool
    {
        return CompetitionDraw::query()
                ->where([
                    ['competition_id', $this->competition->id],
                    ['round_from', Carbon::parse($dates['from'])->format('Y-m-d')],
                    ['round_to', Carbon::parse($dates['to'])->format('Y-m-d')]
                ])
                ->count() > 0;
    }

    protected function createDraw(array $dates): CompetitionDraw
    {
        return CompetitionDraw::create([
            'competition_id' => $this->competition->id,
            'competition_type' => $this->competition->type,
            'round_from' => Carbon::parse($dates['from'])->format('Y-m-d'),
            'round_to' => Carbon::parse($dates['to'])->format('Y-m-d'),
            'round_hash' => hash('xxh128', "{$dates['from']} {$this->competition->id}"),
            'drawn_by' => $this->drawnBy,
        ]);
    }
}

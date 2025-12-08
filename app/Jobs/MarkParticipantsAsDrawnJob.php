<?php

namespace App\Jobs;

use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkParticipantsAsDrawnJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public Competition $competition,
        public ?string $drawnBy = null,
        public CompetitionDraw $competitionDraw,
        public array $dates
    )
    {
    }

    public function handle(): void
    {
        Participant::query()
            ->where('competition_id', $this->competition->id)
            ->whereNull('drawn_at')
            ->whereBetween('call_start', $this->dates)
            ->update([
                'competition_draw_id' => $this->competitionDraw->id,
                'drawn_at' => now(),
            ]);
    }
}

<?php

namespace Tests\Feature\API\Competition\Draw;

use App\Models\Competition;
use App\Models\CompetitionDraw;
use App\Models\Participant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DrawHashTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->login();

        $this->competitionA = Competition::factory([
            'name' => 'Test Competition',
            'type' => 'WHOLE_COMPETITION',
            'start' => now()->subDays(7),
            'end' => now()->subMinute(),
            ])->create();
    }

    public function test_round_hash_building()
    {
        $this->post(route('competition.mark-as-drawn',$this->competitionA))->assertNoContent();

        $from = $this->competitionA->start;

        tap(CompetitionDraw::first(), function (CompetitionDraw $draw) use($from) {
            $hash = hash('xxh128', "{$from} {$this->competitionA->id}");

            $this->assertSame($hash, $draw->round_hash);
        });
    }
}

<?php

namespace Tests\Unit\Job;

use App\DTO\ActiveCall\ActiveCallDTO;
use App\Jobs\CreateEntryJob;
use App\Models\ActiveCall;
use App\Models\EntrantRoundCount;
use App\Models\Participant;
use Tests\TestCase;

class CreateEntryJobTest extends TestCase
{
    public function test_paying_participant_is_created_only()
    {
        $this->assertCount(0, Participant::all());
        $callerPhoneNumber = '441604556778';

        $activeCall = ActiveCall::factory()->create(['caller_phone_number' => $callerPhoneNumber]);

        $this->assertNull($activeCall->participant_id);

        $activeCallDTO = new ActiveCallDTO(
            $activeCall->id,
            $activeCall->organisation_id,
            $activeCall->competition_id,
            $activeCall->call_id,
            $activeCall->participant_id,
            $activeCall->competition_phone_line_id,
            $activeCall->phone_number,
            $activeCall->caller_phone_number,
            $activeCall->status,
            $activeCall->round_start,
            $activeCall->round_end,
            null,
            $activeCall->cli_presentation,
            null,
            $activeCall->created_at,
            $activeCall->updated_at
        );

        CreateEntryJob::dispatchSync($activeCallDTO);

        $this->assertCount(1, $participants = Participant::all());

        $participant = $participants->first();

        $activeCall->refresh();

        $this->assertSame('COMP_OPEN_ANSWERED', $activeCall->status);

        $this->assertSame($activeCall->participant_id, $participant->id);

        tap($participant, function ($participant) use ($activeCall) {
            $this->assertNotNull($participant->uuid);
            $this->assertSame($activeCall->call_id, $participant->call_id);
            $this->assertSame($activeCall->competition_id, $participant->competition_id);
            $this->assertSame($activeCall->cli_presentation, $participant->cli_presentation);
            $this->assertFalse($participant->is_free_entry);
            $this->assertNull($participant->station_name);
            $this->assertSame($activeCall->phone_number, $participant->competition_phone_number);
            $this->assertSame($activeCall->caller_phone_number, $participant->telephone);

            $this->assertSame($activeCall->round_start, $participant->round_start->format('Y-m-d H:i:s'));
            $this->assertSame($activeCall->round_end, $participant->round_end->format('Y-m-d H:i:s'));

            $this->assertSame($activeCall->created_at->format('Y-m-d H:i:s'), $participant->call_start->format('Y-m-d H:i:s'));
            $this->assertNull($participant->call_end);

            $this->assertSame($activeCall->participant_id, $participant->id);
        });

        $this->assertCount(1, $roundCounts = EntrantRoundCount::all());

        $roundCount = $roundCounts->first();

        tap($roundCount, function ($roundCount) use ($activeCallDTO, $callerPhoneNumber) {
            $hash = hash('xxh128', "{$activeCallDTO->competition_id} {$callerPhoneNumber}");

            $this->assertSame($hash, $roundCount->hash);
            $this->assertSame($activeCallDTO->competition_id, $roundCount->competition_id);
            $this->assertSame($callerPhoneNumber, $roundCount->caller_number);
            $this->assertSame(1, $roundCount->total_entry_count);
        });
    }
}

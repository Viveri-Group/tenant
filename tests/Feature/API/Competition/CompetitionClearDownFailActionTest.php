<?php

namespace Tests\Feature\API\Competition;

use App\Action\Competition\CompetitionClearDownFailAction;
use App\Models\ActiveCall;
use App\Models\FailedEntry;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CompetitionClearDownFailActionTest extends TestCase
{
    public function test_status_of_fail()
    {
        Bus::fake();
        $this->login();

        $activeCall = ActiveCall::factory()->hasCompetitionPhoneLine()->create();
        $activeCallDTO = $this->getActiveCallDTO($activeCall);

        (new CompetitionClearDownFailAction())->handle($activeCallDTO, 'failedFoo');

        $failedEntry = FailedEntry::first();

        tap($failedEntry, function (FailedEntry $failedEntry) use ($activeCallDTO) {
            $this->assertSame($activeCallDTO->competition_id, $failedEntry->competition_id);
            $this->assertEquals($activeCallDTO->call_id, $failedEntry->call_id);
            $this->assertSame($activeCallDTO->competition_phone_number, $failedEntry->phone_number);
            $this->assertSame($activeCallDTO->caller_phone_number, $failedEntry->caller_phone_number);
            $this->assertSame('failedFoo', $failedEntry->reason);
            $this->assertSame($activeCallDTO->created_at, $failedEntry->call_start->format('Y-m-d H:i:s') );
            $this->assertSame($activeCallDTO->call_end, $failedEntry->call_end->format('Y-m-d H:i:s') );
        });
    }
}

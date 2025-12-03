<?php

namespace Tests\Unit\Action\Competition;

use App\Action\Competition\CompetitionPreCheckAction;
use App\DTO\Competition\CompetitionPreCheckRequestDTO;
use App\Exceptions\CompetitionClosedHTTPException;
use App\Exceptions\NoActiveCompetitionsHTTPException;
use App\Exceptions\PhoneBookEntryMissingHTTPException;
use App\Http\Resources\CompetitionCapacityCheckWithActivePhoneLineResource;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\Organisation;
use App\Models\PhoneBookEntry;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CompetitionPreCheckActionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $this->setFileDefaults();

        $this->login();

        $this->organisation = Organisation::factory()->create();

        $this->competitionNumber = '0333456555';

        PhoneBookEntry::factory(['phone_number' => $this->competitionNumber , 'organisation_id' => $this->organisation->id])->create();

        $this->competition = Competition::factory(['start' => now()->subDays(2), 'end' => now()->addDay(), 'max_entries' => 2, 'organisation_id' => $this->organisation->id])
            ->hasPhoneLines(['phone_number' => $this->competitionNumber, 'organisation_id' => $this->organisation->id])
            ->create();

        $this->phoneline = CompetitionPhoneLine::first();
    }

    public function test_407_is_returned()
    {
        try {
            (new CompetitionPreCheckAction())->handle(
                new CompetitionPreCheckRequestDTO(
                    '441604464237',
                    '441111111111',
                    '1234'
                ),
                $this->phoneline,
                CompetitionCapacityCheckWithActivePhoneLineResource::class
            );

            $this->fail('Expected exception was not thrown');
        } catch (PhoneBookEntryMissingHTTPException $e) {
            $this->assertEquals(407, $e->getCode());
            $this->assertStringContainsString('No Phone Book entry exists.', $e->getMessage());
        }
    }

    public function test_400_is_returned()
    {
        try {
            (new CompetitionPreCheckAction())->handle(
                new CompetitionPreCheckRequestDTO(
                    '441604464237',
                    $this->competitionNumber,
                    '1234'
                ),
                null,
                CompetitionCapacityCheckWithActivePhoneLineResource::class
            );

            $this->fail('Expected exception was not thrown');
        } catch (NoActiveCompetitionsHTTPException $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertStringContainsString('No competitions associated with this phone line.', $e->getMessage());
        }
    }

    public function test_competition_is_closed_returns_200()
    {
        $this->competition->update(['end' => now()->subDay()]);

        try {
            (new CompetitionPreCheckAction())->handle(
                new CompetitionPreCheckRequestDTO(
                    '441604464237',
                    $this->competitionNumber,
                    '1234'
                ),
                $this->phoneline,
                CompetitionCapacityCheckWithActivePhoneLineResource::class
            );

            $this->fail('Expected exception was not thrown');
        } catch (CompetitionClosedHTTPException $e) {
            $this->assertEquals(200, $e->getCode());
            $this->assertStringContainsString('Competition is closed.', $e->getMessage());
        }
    }
}

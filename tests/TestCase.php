<?php

namespace Tests;

use App\DTO\ActiveCall\ActiveCallDTO;
use App\Enums\CompetitionAudioType;
use App\Models\ActiveCall;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\FileDefault;
use App\Models\Organisation;
use App\Models\PhoneBookEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Http::preventStrayRequests();
    }

    protected function setCompetition(): array
    {
        $organisation = Organisation::factory()->create();

        $this->setFileDefaults($organisation->id);

        $competitionNumber = '0333456555';

        $phoneBookEntry = PhoneBookEntry::factory(['phone_number' => $competitionNumber, 'organisation_id' => $organisation->id])->create();

        $competition = Competition::factory(['start' => now()->subDays(2), 'end' => now()->addDay(), 'max_entries' => 4, 'organisation_id' => $organisation->id])
            ->hasPhoneLines(['phone_number' => $competitionNumber, 'organisation_id' => $organisation->id])
            ->create();

        $phoneLine = CompetitionPhoneLine::first();

        $callerNumber = '441604464237';

        return [
            $organisation,
            $phoneBookEntry,
            $competition,
            $phoneLine,
            $competitionNumber,
            $callerNumber
        ];
    }

    protected function login(array $userDetails = []): User
    {
        $user = User::factory()->create($userDetails);

        $this->actingAs($user);

        return $user;
    }

    protected function setFileDefaults(int $organisationId): void
    {
        collect(CompetitionAudioType::names())->each(fn($type, $index) => FileDefault::factory([
            'organisation_id' => $organisationId,
            'type' => CompetitionAudioType::from($type),
            'external_id' => $index+1
        ])->create());
    }

    protected function getActiveCallDTO(ActiveCall $activeCall): ActiveCallDTO
    {
        return new ActiveCallDTO(
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
            $activeCall->call_end ?? now(),
            $activeCall->cli_presentation,
            null,
            $activeCall->created_at,
            $activeCall->updated_at
        );
    }
}

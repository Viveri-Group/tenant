<?php

namespace Tests;

use App\DTO\ActiveCall\ActiveCallDTO;
use App\Enums\CompetitionAudioType;
use App\Models\ActiveCall;
use App\Models\FileDefault;
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

    protected function login(array $userDetails = []): User
    {
        $user = User::factory()->create($userDetails);

        $this->actingAs($user);

        return $user;
    }

    protected function setFileDefaults(): void
    {
        collect(CompetitionAudioType::names())->each(fn($type, $index) => FileDefault::factory(['type' => CompetitionAudioType::from($type), 'external_id' => $index+1])->create());
    }

    protected function getActiveCallDTO(ActiveCall $activeCall): ActiveCallDTO
    {
        return new ActiveCallDTO(
            $activeCall->id,
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

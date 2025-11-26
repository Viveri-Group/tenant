<?php

namespace Tests\Unit\Action\Competition;

use App\Action\Competition\RemoveFailedEntryParticipantsAction;
use App\DTO\ActiveCall\ActiveCallDTO;
use App\Models\ActiveCall;
use App\Models\EntrantRoundCount;
use App\Models\Participant;
use Tests\TestCase;

class RemoveFailedEntryParticipantsActionTest extends TestCase
{
    protected ActiveCallDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();

        $activeCall = ActiveCall::factory()->create(['call_id' => '123456']);

        $this->dto = $this->getActiveCallDTO($activeCall);
    }

    public function test_it_deletes_participants_and_updates_round_count(): void
    {
        // Arrange: 2 paid + 1 free
        Participant::factory()->create(['call_id' => $this->dto->call_id, 'is_free_entry' => false]);
        Participant::factory()->create(['call_id' => $this->dto->call_id, 'is_free_entry' => false]);
        Participant::factory()->create(['call_id' => $this->dto->call_id, 'is_free_entry' => true]);

        $round = EntrantRoundCount::factory()->create([
            'hash' => hash('xxh128', "{$this->dto->competition_id} {$this->dto->caller_phone_number}"),
            'total_entry_count' => 10,
        ]);

        (new RemoveFailedEntryParticipantsAction())->handle($this->dto);

        // Assert: participants deleted
        $this->assertDatabaseMissing('participants', [
            'call_id' => (string) $this->dto->call_id,
            'deleted_at' => null,
        ]);

        // Assert: round counts decremented
        $round->refresh();
        $this->assertEquals(7, $round->total_entry_count); // 10 - 3
    }

    public function test_it_deletes_participants_but_skips_round_count_if_none_exists(): void
    {
        Participant::factory()->count(2)->create(['call_id' => $this->dto->call_id]);

        // Act (no EntrantRoundCount record)
        (new RemoveFailedEntryParticipantsAction())->handle($this->dto);

        // Assert: participants deleted
        $this->assertDatabaseMissing('participants', [
            'call_id' => (string) $this->dto->call_id,
            'deleted_at' => null,
        ]);

        // No error thrown, nothing updated
        $this->assertEquals(0, EntrantRoundCount::count());
    }

    public function test_it_never_allows_negative_counts(): void
    {
        // Arrange: 2 participants, but round count has only 1 total/paid
        Participant::factory()->count(2)->create(['call_id' => $this->dto->call_id, 'is_free_entry' => false]);

        $round = EntrantRoundCount::factory()->create([
            'hash' => hash('xxh128', "{$this->dto->competition_id} {$this->dto->caller_phone_number}"),
            'total_entry_count' => 1,
        ]);

        // Act
        (new RemoveFailedEntryParticipantsAction())->handle($this->dto);

        // Assert: clamped to 0
        $round->refresh();
        $this->assertEquals(0, $round->total_entry_count);
    }

    public function test_it_handles_no_participants_gracefully(): void
    {
        $round = EntrantRoundCount::factory()->create([
            'hash' => hash('xxh128', "{$this->dto->competition_id} {$this->dto->caller_phone_number}"),
            'total_entry_count' => 5,
        ]);

        // Act (no participants to delete)
        (new RemoveFailedEntryParticipantsAction())->handle($this->dto);

        // Assert: round unchanged
        $round->refresh();
        $this->assertEquals(5, $round->total_entry_count);
    }
}

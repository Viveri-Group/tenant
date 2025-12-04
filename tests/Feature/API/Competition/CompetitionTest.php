<?php

namespace Tests\Feature\API\Competition;

use App\Models\Competition;
use App\Models\Organisation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CompetitionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->login();

        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->organisation = Organisation::factory()->create();
    }
    public function test_can_get_competitions()
    {
        $startA = now()->addMinute();
        $endA = now()->addDay();

        $startB = now()->addDays(3);
        $endB = now()->addDays(6);

        $competitionA = Competition::factory()->create([
            'name' => 'Test Competition A',
            'start' => $startA,
            'end' => $endA,
            'max_entries' => 5,
        ]);

        $competitionB = Competition::factory()->create([
            'name' => 'Test Competition B',
            'start' => $startB,
            'end' => $endB,
        ]);

        $this->get(route('competition.index'))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($competitionA, $competitionB, $startA, $endA, $startB, $endB) {
                return $json
                    ->where('data.0.type', 'competition')
                    ->where('data.0.id', $competitionB->id)
                    ->where('data.0.attributes.name', 'Test Competition B')
                    ->where('data.0.attributes.is_open', false)
                    ->where('data.0.attributes.start', '2024-01-04T09:00:00+00:00')
                    ->where('data.0.attributes.end', '2024-01-07T09:00:00+00:00')

                    ->where('data.1.type', 'competition')
                    ->where('data.1.id', $competitionA->id)
                    ->where('data.1.attributes.name', 'Test Competition A')
                    ->where('data.1.attributes.is_open', false)
                    ->where('data.1.attributes.start', '2024-01-01T09:01:00+00:00')
                    ->where('data.1.attributes.end', '2024-01-02T09:00:00+00:00')

                    ->has('links')
                    ->has('meta');
            });
    }

    public function test_can_get_competitions_on_page_2()
    {
        $competitionA = Competition::factory()->create([
            'name' => 'Test Competition A PAGE 2',
            'start' => now()->addDay(),
            'end' => now()->addDays(2),
            'max_entries' => 5,
        ]);

        $competitionB = Competition::factory()->create([
            'name' => 'Test Competition B PAGE 2',
            'start' => now()->addDays(2),
            'end' => now()->addDays(3),
        ]);

        Competition::factory()->count(50)->create([
            'name' => 'Test Competition PAGE 1',
            'start' => now()->addDays(10),
            'end' => now()->addDays(11),
            'max_entries' => 5,
        ]);

        $this->get(route('competition.index', ['page' => 2]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($competitionA, $competitionB) {
                return $json
                    ->where('data.1.type', 'competition')
                    ->where('data.1.id', $competitionA->id)
                    ->where('data.1.attributes.name', 'Test Competition A PAGE 2')
                    ->where('data.1.attributes.is_open', false)

                    ->where('data.0.type', 'competition')
                    ->where('data.0.id', $competitionB->id)
                    ->where('data.0.attributes.name', 'Test Competition B PAGE 2')
                    ->where('data.0.attributes.is_open', false)
                    ->has('links')
                    ->has('meta');
            });
    }

    public function test_can_create_competition()
    {
        $start = now()->addMinute();
        $end = now()->addDay();

        $this->assertCount(0, Competition::all());

        $this->post(route('competition.create'), [
            'organisation_id' => $this->organisation->id,
            'name' => 'Test Competition',
            'start' => $start,
            'end' => $end,
            'max_entries' => 5,
        ])
            ->assertCreated()
            ->assertJson(function (AssertableJson $json) use($start, $end) {
                return $json
                    ->where('data.type', 'competition')
                    ->has('data.id')
                    ->where('data.attributes.name', 'Test Competition')
                    ->where('data.attributes.is_open', false)
                    ->where('data.attributes.start', '2024-01-01T09:01:00+00:00')
                    ->where('data.attributes.end', '2024-01-02T09:00:00+00:00')
                    ->where('data.attributes.max_entries', 5)
                    ->where('data.attributes.sms_mask', null)
                    ->where('data.attributes.sms_offer_enabled', false)
                    ->where('data.attributes.sms_offer_message', null)
                    ->where('data.attributes.sms_first_entry_enabled', false)
                    ->where('data.attributes.sms_first_entry_message', null)
                    ->has('data.relationships.organisation')
                    ->has('data.relationships.phone_lines')
                    ->has('data.relationships.winner')
                    ->has('data.relationships.files')
                    ->has('data.relationships.draws');
            });

        $this->assertCount(1, $competitions = Competition::all());

        tap($competitions->first(), function (Competition $competition) use($start, $end) {
           $this->assertSame('Test Competition', $competition->name);
           $this->assertSame('2024-01-01T09:01:00+00:00', $competition->start->toIso8601String());
           $this->assertSame('2024-01-02T09:00:00+00:00', $competition->end->toIso8601String());
        });
    }

    public function test_cannot_create_competition_with_start_date_before_the_end_date()
    {
        $start = now()->addDays(5);
        $end = now()->addDays(4);

        $this->assertCount(0, Competition::all());

        $this->post(route('competition.create'), [
            'organisation_id' => $this->organisation->id,
            'name' => 'Test Competition',
            'start' => $start,
            'end' => $end,
            'max_entries' => 5,
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.message', 'The start field must be a date before end.')
                    ->where('data.0.source', 'start')
                    ->where('data.1.message', 'The end field must be a date after start.')
                    ->where('data.1.source', 'end');
            });

        $this->assertCount(0, Competition::all());
    }

    public function test_can_create_competition_with_start_and_end_date_in_the_past()
    {
        Carbon::setTestNow('2004-01-10 00:00:00');

        $this->assertCount(0, Competition::all());

        $this->post(route('competition.create'), [
            'organisation_id' => $this->organisation->id,
            'name' => 'Test Competition',
            'start' => now()->subDays(5),
            'end' => now()->subDay(),
            'max_entries' => 5,
        ])
            ->assertCreated();

        $this->assertCount(1, Competition::all());
    }

    public function test_can_get_competition()
    {
        $competition = Competition::factory()->create([
            'name' => 'Test Competition',
            'start' => '2024-10-01 09:00:00',
            'end' => '2024-10-08 09:00:00',
            'max_entries' => 5,
        ]);

        $this->get(route('competition.show', $competition))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($competition) {
                return $json
                ->where('data.type', 'competition')
                    ->where('data.id', $competition->id)
                    ->where('data.attributes.name', 'Test Competition')
                    ->where('data.attributes.is_open', false)
                    ->where('data.attributes.start', Carbon::parse('2024-10-01 09:00:00')->toIso8601String())
                    ->where('data.attributes.end', Carbon::parse('2024-10-08 09:00:00')->toIso8601String())
                    ->has('data.relationships.phone_lines', 0)
                    ->has('data.relationships.winner', 0)
                    ->has('data.relationships.draws', 0);
            });
    }

    public function test_get_on_non_existent_competition_returns_404()
    {
        $this->get(route('competition.show', 5))
            ->assertNotFound();
    }

    public function test_can_update_competition()
    {
        $start = now()->addMinute();
        $end = now()->addDay();

        $updatedStart = now()->addDay();
        $updatedEnd = now()->addDays(2);

        $competition = Competition::factory()->create([
            'organisation_id' => $this->organisation->id,
            'name' => 'Test Competition',
            'start' => $start,
            'end' => $end,
            'max_entries' => 5,
        ]);

        $this->assertCount(1, Competition::all());

        $this->post(route('competition.update', $competition), [
            'organisation_id' => $this->organisation->id,
            'name' => 'Test Competition FOO',
            'start' => $updatedStart,
            'end' => $updatedEnd,
            'max_entries' => 5,
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use($competition, $updatedStart, $updatedEnd) {
                return $json
                    ->where('data.type', 'competition')
                    ->where('data.id', $competition->id)
                    ->where('data.attributes.name', 'Test Competition FOO')
                    ->where('data.attributes.is_open', false)
                    ->where('data.attributes.start', $updatedStart->toIso8601String())
                    ->where('data.attributes.end', $updatedEnd->toIso8601String())
                    ->where('data.attributes.max_entries', 5)
                    ->where('data.attributes.sms_mask', null)
                    ->where('data.attributes.sms_offer_enabled', false)
                    ->where('data.attributes.sms_offer_message', null)
                    ->where('data.attributes.sms_first_entry_enabled', false)
                    ->where('data.attributes.sms_first_entry_message', null)
                    ->has('data.relationships.organisation')
                    ->has('data.relationships.phone_lines')
                    ->has('data.relationships.winner')
                    ->has('data.relationships.files')
                    ->has('data.relationships.draws');
            });

        $this->assertCount(1, $competitions = Competition::all());

        tap($competitions->first(), function (Competition $competition) use($updatedStart, $updatedEnd) {
            $this->assertSame('Test Competition FOO', $competition->name);
            $this->assertSame('2024-01-02 09:00:00', $competition->start->toDateTimeString());
            $this->assertSame('2024-01-03 09:00:00', $competition->end->toDateTimeString());
        });
    }

    public function test_cannot_update_competitions_start_date_when_comp_has_already_started()
    {
        $start = now()->subSecond();
        $end = now()->addDay();

        $updatedStart = now()->addDay();
        $updatedEnd = now()->addDays(2);

        $competition = Competition::factory()->create([
            'organisation_id' => $this->organisation->id,
            'name' => 'Test Competition',
            'start' => $start,
            'end' => $end,
            'max_entries' => 5,
        ]);

        $this->assertCount(1, Competition::all());

        $this->post(route('competition.update', $competition), [
            'organisation_id' => $this->organisation->id,
            'name' => 'Test Competition FOO',
            'start' => $updatedStart,
            'end' => $updatedEnd,
            'max_entries' => 5,
        ])
            ->assertConflict()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('message', 'Unable to update competition start_date after the competition has started.');
            });
    }

    public function test_can_delete_competition()
    {
        $competition = Competition::factory()->create([
            'name' => 'Test Competition',
            'start' => '2024-01-01 15:00:00',
            'end' => '2024-01-02 15:00:00',
            'max_entries' => 5,
        ]);

        $this->assertCount(1, Competition::all());

        $this->delete(route('competition.destroy', $competition))
            ->assertNoContent();

        $this->assertCount(0, Competition::all());
    }

    public function test_receive_validation_errors()
    {
        $this->post(route('competition.create'))
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.source', 'organisation_id')
                    ->where('data.1.source', 'name')
                    ->where('data.2.source', 'start')
                    ->where('data.3.source', 'end')
                    ->etc();
            });
    }

    public function test_cannot_create_with_invalid_special_offer()
    {
        $this->post(route('competition.create'), [
            'organisation_id' => $this->organisation->id,
            'name' => 'Test Competition',
            'start' => now(),
            'end' => now()->addDay(),
            'max_entries' => 5,
            'special_offer' => 'FOO'
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.source', 'special_offer');
            });
    }
}

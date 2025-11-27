<?php

namespace Tests\Feature\API\PhoneLine;

use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\Organisation;
use App\Models\PhoneBookEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PhoneLineTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->login();

        $this->organisation = Organisation::factory()->create();

        $this->competition = Competition::factory(['organisation_id' => $this->organisation->id])->create();

        PhoneBookEntry::factory()->create(['phone_number' => '07222333333', 'organisation_id' => $this->organisation->id]);
        PhoneBookEntry::factory()->create(['phone_number' => '07222555555', 'organisation_id' => $this->organisation->id]);
        PhoneBookEntry::factory()->create(['phone_number' => '07222666666', 'organisation_id' => $this->organisation->id]);
        PhoneBookEntry::factory()->create(['phone_number' => '072227777777', 'organisation_id' => $this->organisation->id]);
        PhoneBookEntry::factory()->create(['phone_number' => '07555333333', 'organisation_id' => $this->organisation->id]);
    }

    public function test_can_create_phone_line()
    {
        $this->assertCount(0, CompetitionPhoneLine::all());

        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '07222 555555',
        ])->assertCreated();

        $this->assertCount(1, $phoneLines = CompetitionPhoneLine::all());

        tap($phoneLines->first(), function (CompetitionPhoneLine $line) {
            $this->assertSame('07222555555', $line->phone_number);
            $this->assertSame($this->competition->id, $line->competition_id);
        });
    }

    public function test_cant_create_phone_line_if_comp_and_phone_book_entry_org_id_mismatch()
    {
        $orgB = Organisation::factory()->create();

        PhoneBookEntry::factory()->create(['phone_number' => '441111111111', 'organisation_id' => $orgB->id]);

        $this->assertCount(0, CompetitionPhoneLine::all());

        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '441111111111',
        ])->assertConflict();

        $this->assertCount(0, CompetitionPhoneLine::all());
    }

    public function test_cant_create_phone_line_not_in_phone_book()
    {
        $this->assertCount(0, CompetitionPhoneLine::all());

        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '07222000000',
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
            return $json
                ->has('message')
                ->where('data.0.source', 'phone_number')
                ->etc();
        });
    }

    public function test_can_create_multiple_phone_lines_against_one_competition()
    {
        $this->assertCount(0, CompetitionPhoneLine::all());

        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '07222 555555',
        ])->assertCreated();

        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '07222 666666',
        ])->assertCreated();

        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '07222 7777777',
        ])->assertCreated();

        $this->assertCount(3, $phoneLines = CompetitionPhoneLine::all());

        tap($phoneLines, function (Collection $lines) {
            $this->assertSame('07222555555', $lines->get(0)->phone_number);
            $this->assertSame($this->competition->id, $lines->get(0)->competition_id);

            $this->assertSame('07222666666', $lines->get(1)->phone_number);
            $this->assertSame($this->competition->id, $lines->get(1)->competition_id);

            $this->assertSame('072227777777', $lines->get(2)->phone_number);
            $this->assertSame($this->competition->id, $lines->get(2)->competition_id);
        });
    }

    public function test_cannot_create_phone_line_more_than_once()
    {
        Competition::factory()->hasPhoneLines(1, ['phone_number' => '07222555555'])->create();

        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '07222 555555',
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json){
                return $json
                    ->where('message', 'Phone number (07222 555555) is already in use.')
                    ->etc();
            });
    }

    public function test_cannot_add_phone_line_more_than_once_to_a_competition()
    {
        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '07222 555555',
        ])
            ->assertCreated();

        $this->post(route('phone-line.create', $this->competition), [
            'phone_number' => '07222 555555',
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('message', 'The phone line 07222 555555 is already associated with this competition.')
                    ->etc();
            });
    }

    public function test_can_get_phone_line()
    {
        $phoneLine = CompetitionPhoneLine::factory()->create([
            'competition_id' => $this->competition->id,
            'phone_number' => '07222 555555'
        ]);

        $this->get(route('phone-line.show', [$this->competition, $phoneLine]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($phoneLine) {
                return $json
                    ->where('data.type', 'phone-line')
                    ->where('data.id', $phoneLine->id)
                    ->where('data.attributes.number', '07222555555')
                    ->where('data.attributes.competition_id', $this->competition->id);
            });
    }

    public function test_get_of_non_existent_phone_line_returns_404()
    {
        $this->get(route('phone-line.show', [$this->competition, 5]))
            ->assertNotFound();
    }

    public function test_can_update_phone_line()
    {
        $phoneLine = CompetitionPhoneLine::factory()->create([
            'competition_id' => $this->competition->id,
            'phone_number' => '07222 555555'
        ]);

        $this->assertCount(1, CompetitionPhoneLine::all());

        $this->post(route('phone-line.update', [$this->competition, $phoneLine]), [
            'phone_number' => '07555 333333'
        ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($phoneLine) {
                return $json
                    ->where('data.type', 'phone-line')
                    ->where('data.id', $phoneLine->id)
                    ->where('data.attributes.number', '07555333333')
                    ->where('data.attributes.competition_id', $this->competition->id);
            });

        $this->assertCount(1, $phoneLines = CompetitionPhoneLine::all());

        tap($phoneLines->first(), function (CompetitionPhoneLine $line) {
            $this->assertSame('07555333333', $line->phone_number);
            $this->assertSame($this->competition->id, $line->competition_id);
        });
    }

    public function test_cant_update_phone_line_to_a_number_not_in_the_phone_book()
    {
        $phoneLine = CompetitionPhoneLine::factory()->create([
            'competition_id' => $this->competition->id,
            'phone_number' => '07222 555555'
        ]);

        $this->assertCount(1, CompetitionPhoneLine::all());

        $this->post(route('phone-line.update', [$this->competition, $phoneLine]), [
            'phone_number' => '07555 000000'
        ])
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.source', 'phone_number')
                    ->etc();
            });
    }

    public function test_cannot_update_phone_line_with_wrong_competition_id()
    {
        $competition2 = Competition::factory()->create();

        $phoneLine = CompetitionPhoneLine::factory()->create([
            'phone_number' => '07222 555555'
        ]);

        $this->post(route('phone-line.update', [$competition2, $phoneLine]), [
            'phone_number' => '07555 333333'
        ])
            ->assertNotFound();
    }

    public function test_can_delete_phone_line()
    {
        $phoneLine = CompetitionPhoneLine::factory()->create([
            'competition_id' => $this->competition->id,
            'phone_number' => '07222 555555'
        ]);

        $this->assertCount(1, CompetitionPhoneLine::all());

        $this->delete(route('phone-line.destroy', [$this->competition, $phoneLine]))
            ->assertNoContent();

        $this->assertCount(0, CompetitionPhoneLine::all());
    }

    public function test_receive_validation_errors()
    {
        $this->post(route('phone-line.create', $this->competition))
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->has('message')
                    ->where('data.0.source', 'phone_number')
                    ->etc();
            });
    }
}

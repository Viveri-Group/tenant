<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Organisation;
use App\Models\Participant;
use App\Models\CompetitionPhoneLine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'competition_id' => Competition::factory(),
            'call_id' => $this->faker->numerify('####'),
            'call_start' => now(),
            'call_end' => now()->addSeconds(10),
            'competition_phone_number' => $this->faker->numerify('############'),
            'telephone' => $this->faker->numerify('############'),
            'station_name' => $this->faker->word(),
            'round_start' => now(),
            'round_end' => now()->addMinutes(10),

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

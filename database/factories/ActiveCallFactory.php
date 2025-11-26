<?php

namespace Database\Factories;

use App\Models\ActiveCall;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ActiveCallFactory extends Factory
{
    protected $model = ActiveCall::class;

    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'competition_id' => Competition::factory(),
            'call_id' => $this->faker->randomNumber(),
            'competition_phone_line_id' => CompetitionPhoneLine::factory(),
            'phone_number' => $this->faker->numerify('############'),
            'caller_phone_number' => $this->faker->numerify('############'),
            'status' => 'OPEN',
            'cli_presentation' => 2,
            'round_start' => '2024-01-01 10:00:00',
            'round_end' => '2024-01-10 10:00:00',
            'call_end' => now()->addMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function hasCompetitionPhoneLine(): ActiveCallFactory
    {
        return $this->state(function (array $attributes) {
            $competition = Competition::factory()->hasPhoneLines()->create();

            return [
                'competition_id' => $competition->id,
                'phone_number' => $competition->phoneLines()->first()->phone_number,
            ];
        });
    }
}

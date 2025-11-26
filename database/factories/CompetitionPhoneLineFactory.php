<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CompetitionPhoneLineFactory extends Factory
{
    protected $model = CompetitionPhoneLine::class;

    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'competition_id' => Competition::factory(),
            'phone_number' => $this->faker->numerify('############'),

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ActiveCallOrphan;
use App\Models\Competition;
use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ActiveCallOrphanFactory extends Factory
{
    protected $model = ActiveCallOrphan::class;

    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'competition_id' => null,
            'call_id' => $this->faker->randomNumber(),
            'phone_number' => $this->faker->numerify('############'),
            'caller_phone_number' => $this->faker->numerify('############'),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Organisation;
use App\Models\PhoneBookEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PhoneBookEntryFactory extends Factory
{
    protected $model = PhoneBookEntry::class;

    public function definition(): array
    {

        return [
            'organisation_id' => Organisation::factory(),
            'phone_number' => $this->faker->numerify('44##########'),

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

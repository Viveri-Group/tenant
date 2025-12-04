<?php

namespace Database\Factories;

use App\Enums\CompetitionAudioType;
use App\Models\FileDefault;
use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FileDefaultFactory extends Factory
{
    protected $model = FileDefault::class;

    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'external_id' => rand(15, 5000),
            'type'=> $this->faker->randomElement(CompetitionAudioType::names()),
            'filename'=>$this->faker->word(),
            'mime_type'=>$this->faker->word(),
            'extension'=>$this->faker->word(),

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }


}

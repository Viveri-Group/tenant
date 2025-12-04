<?php

namespace App\Action\File;

use App\Models\FileDefault;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GetCompetitionDefaultAudioAction
{
    public function __construct(public string $organisationId, public array $expectedFileTypes)
    {
    }

    public function handle(array $audioFiles = []): array
    {
        return (new FormatAudioAction($this->expectedFileTypes))->handle($audioFiles, $this->getDefaultAudio());
    }

    protected function getDefaultAudio(): Collection
    {
        return Cache::remember(
            "default_audio_files__{$this->organisationId}__" . collect($this->expectedFileTypes)->implode('__'),
            now()->addMinute(),
            fn() => FileDefault::query()
                ->where('organisation_id', $this->organisationId)
                ->whereIn('type', $this->expectedFileTypes)
                ->get()
        );
    }
}

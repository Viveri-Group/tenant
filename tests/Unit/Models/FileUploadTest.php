<?php

namespace Tests\Unit\Models;

use App\Models\Competition;
use App\Models\FileUpload;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    public function test_it_belongs_to_a_competition()
    {
        $competition = Competition::factory()->create();
        $fileUpload = FileUpload::factory()->create(['competition_id' => $competition->id]);

        $this->assertInstanceOf(Competition::class, $fileUpload->competition);
        $this->assertEquals($competition->id, $fileUpload->competition->id);
    }

    public function test_it_has_a_local_base_constant()
    {
        $this->assertEquals('uploads/', FileUpload::LOCAL_BASE);
    }
}

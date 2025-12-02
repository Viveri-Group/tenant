<?php

namespace Tests\Feature\API\FileUpload;

use App\Enums\CompetitionAudioType;
use App\Jobs\UploadAudioToShout;
use App\Models\Competition;
use App\Models\CompetitionPhoneLine;
use App\Models\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Helpers\Mocks\Shout;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->login();

        $this->competition = Competition::factory()->create();
    }

    public function test_you_can_upload_a_file_to_a_competition()
    {
        Shout::fake();

        Config::set('system.SHOUT_SERVER.STAGING',
            [
                [
                    'identifier' => 'TEST SERVER ONE',
                    'ip_address' => '3.3.3.3',
                    'username' => 'username_1',
                    'password' => 'password_1'
                ]
            ]
        );

        $this->assertCount(0, FileUpload::all());

        $file = UploadedFile::fake()->createWithContent('foo.wav', 'test content');

        $this->post(route('file.store', $this->competition), [
            'file' => $file,
            'type' => CompetitionAudioType::DTMF_MENU->name,
            'name' => 'My New Audio File',
            'audio_type' => 'competition'
        ])->assertCreated();

        $this->assertCount(1, $filesUploaded = FileUpload::all());

        $fileUploaded = $filesUploaded->first();

        tap($fileUploaded, function (FileUpload $fileUpload) {
            $this->assertStringContainsString('foo', $fileUpload->filename);
            $this->assertNotNull($fileUpload->size);
            $this->assertSame('audio/wav', $fileUpload->mime_type);
            $this->assertSame('wav', $fileUpload->extension);
            $this->assertSame('My New Audio File', $fileUpload->name);
            $this->assertSame($this->competition->id, $fileUpload->competition_id);
            $this->assertSame(5, $fileUpload->external_id);
            $this->assertNull($fileUpload->competition_phone_line_id);
        });
    }

    public function test_you_cant_upload_a_file_to_a_competition_if_matching_entry_already_exists()
    {
        Shout::fake();
        Bus::fake();

        Config::set('system.SHOUT_SERVER.STAGING',
            [
                [
                    'identifier' => 'TEST SERVER ONE',
                    'ip_address' => '3.3.3.3',
                    'username' => 'username_1',
                    'password' => 'password_1'
                ]
            ]
        );

        $this->assertCount(0, FileUpload::all());

        $file = UploadedFile::fake()->createWithContent('foo.wav', 'test content');

        FileUpload::factory()->create([
            'type' => CompetitionAudioType::DTMF_MENU->name,
            'competition_id' => $this->competition->id,
        ]);

        $this->post(route('file.store', $this->competition), [
            'file' => $file,
            'type' => CompetitionAudioType::DTMF_MENU->name,
            'name' => 'My New Audio File',
            'audio_type' => 'competition'
        ])->assertForbidden();

        $this->assertCount(1, FileUpload::all());

        Bus::assertNotDispatched(UploadAudioToShout::class);
    }

    public function test_you_can_upload_a_file_to_a_phone_line()
    {
        Bus::fake();

        $phoneLine = CompetitionPhoneLine::factory([
            'competition_id' => $this->competition->id,
        ])->create();

        $this->assertCount(0, FileUpload::all());

        $file = UploadedFile::fake()->create('foo.wav', 1000, 'audio/wav');

        $this->post(route('file.store', $this->competition), [
            'file' => $file,
            'type' => CompetitionAudioType::DTMF_MENU->name,
            'name' => 'My New Audio File',
            'audio_type' => 'competition_phone_line',
            'competition_phone_line_id' => $phoneLine->id
        ])
            ->assertCreated();

        $this->assertCount(1, $filesUploaded = FileUpload::all());

        $fileUploaded = $filesUploaded->first();

        tap($fileUploaded, function (FileUpload $fileUpload) use($phoneLine){
            $this->assertStringContainsString('foo', $fileUpload->filename);
            $this->assertSame(1024000, $fileUpload->size);
            $this->assertSame('audio/wav', $fileUpload->mime_type);
            $this->assertSame('wav', $fileUpload->extension);
            $this->assertSame('My New Audio File', $fileUpload->name);
            $this->assertNull($fileUpload->competition_id);
            $this->assertSame($phoneLine->id, $fileUpload->competition_phone_line_id);
        });

        Bus::assertDispatched(UploadAudioToShout::class, function (UploadAudioToShout $job) use ($fileUploaded) {
            return $fileUploaded->id === $job->fileUpload->id;
        });
    }

    public function test_you_cant_upload_a_file_to_a_phone_line_if_matching_entry_already_exists()
    {
        Bus::fake();

        $phoneLine = CompetitionPhoneLine::factory([
            'competition_id' => $this->competition->id,
        ])->create();

        $this->assertCount(0, FileUpload::all());

        $file = UploadedFile::fake()->create('foo.wav', 1000, 'audio/wav');

        FileUpload::factory()->create([
            'type' => CompetitionAudioType::DTMF_MENU->name,
            'competition_phone_line_id' => $phoneLine->id,
        ]);

        $this->post(route('file.store', $this->competition), [
            'file' => $file,
            'type' => CompetitionAudioType::DTMF_MENU->name,
            'name' => 'My New Audio File',
            'audio_type' => 'competition_phone_line',
            'competition_phone_line_id' => $phoneLine->id
        ])->assertForbidden();

        $this->assertCount(1, FileUpload::all());

        Bus::assertNotDispatched(UploadAudioToShout::class);
    }

    public function test_you_cant_upload_a_file_to_a_competition_with_an_invalid_type()
    {
        Bus::fake();

        $file = UploadedFile::fake()->create('foo.mp3', 1000, 'audio/mpeg');

        $this->post(route('file.store', $this->competition), [
            'file' => $file,
            'type' => 'FOO'
        ])->assertUnprocessable();
    }

    public function test_you_can_associate_a_file_with_a_competition()
    {
        $file = FileUpload::factory()->create(['competition_id' => $this->competition->id]);

        $this->get(route('competition.show', [$this->competition]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($file) {
                return $json
                    ->where('data.id', $this->competition->id)
                    ->where('data.relationships.files.0.type', 'file')
                    ->where('data.relationships.files.0.id', $file->id)
                    ->where('data.relationships.files.0.attributes.filename', $file->filename)
                    ->where('data.relationships.files.0.attributes.size', $file->size)
                    ->where('data.relationships.files.0.attributes.mime_type', $file->mime_type)
                    ->where('data.relationships.files.0.attributes.extension', $file->extension)
                    ->etc();
            });
    }

    public function test_you_can_associate_multiple_files_with_a_competition()
    {
        $fileA = FileUpload::factory()->create(['competition_id' => $this->competition->id]);
        $fileB = FileUpload::factory()->create(['competition_id' => $this->competition->id]);

        $this->get(route('competition.show', [$this->competition]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($fileA, $fileB) {
                return $json
                    ->where('data.id', $this->competition->id)
                    ->where('data.relationships.files.0.type', 'file')
                    ->where('data.relationships.files.0.id', $fileA->id)
                    ->where('data.relationships.files.0.attributes.filename', $fileA->filename)
                    ->where('data.relationships.files.0.attributes.size', $fileA->size)
                    ->where('data.relationships.files.0.attributes.mime_type', $fileA->mime_type)
                    ->where('data.relationships.files.0.attributes.extension', $fileA->extension)
                    ->where('data.relationships.files.1.type', 'file')
                    ->where('data.relationships.files.1.id', $fileB->id)
                    ->where('data.relationships.files.1.attributes.filename', $fileB->filename)
                    ->where('data.relationships.files.1.attributes.size', $fileB->size)
                    ->where('data.relationships.files.1.attributes.mime_type', $fileB->mime_type)
                    ->where('data.relationships.files.1.attributes.extension', $fileB->extension)
                    ->etc();
            });
    }

    public function test_uploaded_wrong_file_type_is_not_allowed()
    {
        $file = FileUpload::factory()->create(['filename' => 'foo.jpg', 'extension' => 'jpg', 'mime_type' => 'image/jpeg']);

        $this->post(route('file.store', $this->competition), [
            'file' => $file,
            'type' => CompetitionAudioType::DTMF_MENU->name
        ])
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('data.0.message', 'The file field must be a file of type: wav.')
                    ->where('data.0.source', 'file')
                    ->etc();
            });
    }

    public function test_default_validation_error_response()
    {
        $this->post(route('file.store', $this->competition))
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('data.0.source', 'file')
                    ->where('data.1.source', 'type')
                    ->where('data.2.source', 'name')
                    ->where('data.3.source', 'audio_type')
                    ->etc();
            });
    }

    public function test_phone_line_file_validation()
    {
        CompetitionPhoneLine::factory([
            'competition_id' => $this->competition->id,
        ])->create();

        $file = UploadedFile::fake()->create('foo.wav', 1000, 'audio/wav');

        $this->post(route('file.store', $this->competition), [
            'file' => $file,
            'type' => CompetitionAudioType::DTMF_MENU->name,
            'name' => 'My New Audio File',
            'audio_type' => 'competition_phone_line',
        ])
            ->assertJson(function (AssertableJson $json) {
                return $json
                    ->where('data.0.source', 'competition_phone_line_id')
                    ->etc();
            });
    }

    public function test_deletion_of_a_file()
    {
        Shout::fake();

        $file = FileUpload::factory()->create(['external_id'=>555, 'competition_id' => $this->competition->id]);

        $this->assertCount(1, FileUpload::all());

        $this->delete(route('file.destroy', [$this->competition, $file]))->assertNoContent();

        $this->assertCount(0, FileUpload::all());
    }
}

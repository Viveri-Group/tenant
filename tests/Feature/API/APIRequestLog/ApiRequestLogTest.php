<?php

namespace Tests\Feature\API\APIRequestLog;

use App\Enums\CompetitionAudioType;
use App\Jobs\CreateAPIRequestLogJob;
use App\Jobs\UpdateAPIRequestLogJob;
use App\Models\APIRequestLog;
use App\Models\Competition;
use App\Models\Organisation;
use App\Models\Participant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ApiRequestLogTest extends TestCase
{
    public function setUp():void
    {
        parent::setUp();

        $this->org = Organisation::factory()->create();
    }

    public function test_api_log_are_written_direct_to_db()
    {
        Config::set('system.LOG_API_REQUEST_USING_QUEUE', false);
        Bus::fake();
        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->login();

        $this->assertCount(0, APIRequestLog::all());

        $response = $this->post(route('competition.create'), [
            'organisation_id' => $this->org->id,
            'name' => 'Test Competition',
            'start' => '2024-01-01 09:00:00',
            'end' => '2024-01-10 09:00:00',
            'max_paid_entries' => 5,
        ])->assertCreated();

        $this->assertNotNull($response->headers->get('x-tracing-id'));

        $this->assertCount(1, $logs = APIRequestLog::all());

        tap($logs->first(), function (APIRequestLog $log) {
            $this->assertSame('2024-01-01 09:00:00', $log->request_input['start']);
            $this->assertSame('2024-01-10 09:00:00', $log->request_input['end']);
            $this->assertSame('Test Competition', $log->request_input['name']);
            $this->assertNull($log->call_id);
            $this->assertNotEmpty($log->response_data);
        });

        Bus::assertNotDispatched(CreateAPIRequestLogJob::class);
        Bus::assertNotDispatched(UpdateAPIRequestLogJob::class);
    }

    public function test_api_log_are_written_direct_to_db_but_response_data_is_empty()
    {
        Config::set('system.LOG_API_REQUEST_USING_QUEUE', false);
        Bus::fake();
        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->login();

        $competition = Competition::factory()->create();
        Participant::factory()->create(['competition_id' => $competition->id, 'call_start' => now()]);

        $this->assertCount(0, APIRequestLog::all());

        $response = $this->post(route('download.entrants', ['competition'=>$competition->id]), [
            'date_from' => now()->subMinute()->format('Y-m-d\TH:i:s\Z'),
            'date_to' => now()->addMinute()->format('Y-m-d\TH:i:s\Z'),
        ])->assertOk();

        $this->assertNotNull($response->headers->get('x-tracing-id'));

        $this->assertCount(1, $logs = APIRequestLog::all());

        tap($logs->first(), function (APIRequestLog $log) {
            $this->assertSame('', $log->response_data);
        });
    }

    public function test_api_log_are_written_with_call_id_data()
    {
        Config::set('system.LOG_API_REQUEST_USING_QUEUE', false);
        Bus::fake();
        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->login();

        $this->assertCount(0, APIRequestLog::all());

        $response = $this->post(route('competition.create'), [
            'organisation_id' => $this->org->id,
            'name' => 'Test Competition',
            'start' => '2024-01-01 09:00:00',
            'end' => '2024-01-10 09:00:00',
            'max_paid_entries' => 5,
            'call_id' => '123456'
        ])->assertCreated();

        $this->assertNotNull($response->headers->get('x-tracing-id'));

        $this->assertCount(1, $logs = APIRequestLog::all());

        tap($logs->first(), function (APIRequestLog $log) {
            $this->assertSame('2024-01-01 09:00:00', $log->request_input['start']);
            $this->assertSame('2024-01-10 09:00:00', $log->request_input['end']);
            $this->assertSame('Test Competition', $log->request_input['name']);
            $this->assertSame(123456, $log->call_id);
        });

        Bus::assertNotDispatched(CreateAPIRequestLogJob::class);
        Bus::assertNotDispatched(UpdateAPIRequestLogJob::class);
    }

    public function test_api_log_jobs_are_sent_to_queue()
    {
        Config::set('system.LOG_API_REQUEST_USING_QUEUE', true);
        Bus::fake();
        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->login();

        $this->assertCount(0, APIRequestLog::all());

        $response = $this->post(route('competition.create'), [
            'organisation_id' => $this->org->id,
            'name' => 'Test Competition',
            'start' => '2024-01-01 09:00:00',
            'end' => '2024-01-10 09:00:00',
            'max_paid_entries' => 5,
        ])->assertCreated();

        $this->assertNotNull($response->headers->get('x-tracing-id'));

        Bus::assertChained([
            function (CreateAPIRequestLogJob $job) {
                $this->assertSame('low', $job->queue);
                $this->assertSame('2024-01-01 09:00:00', $job->requestInput['start']);
                $this->assertSame('2024-01-10 09:00:00', $job->requestInput['end']);
                $this->assertSame('Test Competition', $job->requestInput['name']);

                return true;
            },

            function (UpdateAPIRequestLogJob $job) {
                $this->assertSame('low', $job->queue);
                $this->assertNotEmpty($job->responseData);

                return true;
            },
        ]);
    }

    public function test_api_log_jobs_are_sent_to_queue_but_response_data_is_empty()
    {
        Config::set('system.LOG_API_REQUEST_USING_QUEUE', true);
        Bus::fake();
        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->login();

        $this->assertCount(0, APIRequestLog::all());

        $competition = Competition::factory()->create();
        Participant::factory()->create(['competition_id' => $competition->id, 'call_start' => now()]);

        $this->assertCount(0, APIRequestLog::all());

        $response = $this->post(route('download.entrants', ['competition'=>$competition->id]), [
            'date_from' => now()->subMinute()->format('Y-m-d\TH:i:s\Z'),
            'date_to' => now()->addMinute()->format('Y-m-d\TH:i:s\Z'),
        ])->assertOk();

        $this->assertNotNull($response->headers->get('x-tracing-id'));

        Bus::assertChained([
            function (CreateAPIRequestLogJob $job) {
                $this->assertSame('low', $job->queue);

                return true;
            },

            function (UpdateAPIRequestLogJob $job) {
                $this->assertEmpty($job->responseData);

                return true;
            },
        ]);
    }

    public function test_api_jobs_are_sent_with_file_data()
    {
        Config::set('system.LOG_API_REQUEST_USING_QUEUE', true);
        Bus::fake();

        $this->login();

        $competition = Competition::factory()->create();
        $file = UploadedFile::fake()->create('foo.wav', 1000, 'audio/wav');

        $response = $this->post(route('file.store', $competition), [
            'file' => $file,
            'bar' => 'baz',
            'name' => 'My New Audio',
            'type' => CompetitionAudioType::PRE_EVENT->name,
            'audio_type' => 'competition'
        ])->assertCreated();

        $this->assertNotNull($response->headers->get('x-tracing-id'));

        Bus::assertChained([
            function (CreateAPIRequestLogJob $job) {
                $this->assertSame('low', $job->queue);
                $this->assertSame('foo.wav', $job->requestInput['file']['name']);
                $this->assertSame('audio/wav', $job->requestInput['file']['mimeType']);
                $this->assertSame(1024000, $job->requestInput['file']['size']);
                $this->assertArrayHasKey('originalPath', $job->requestInput['file']);
                $this->assertSame('baz', $job->requestInput['bar']);

                return true;
            },

            function (UpdateAPIRequestLogJob $job) {
                $this->assertSame('low', $job->queue);

                return true;
            },
        ]);
    }
}

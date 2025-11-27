<?php

namespace Tests\Feature\Web\DBHealthCheck;

use App\Models\ActiveCall;
use App\Models\Organisation;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use PDO;
use Tests\TestCase;

class DBHealthCheckTest extends TestCase
{
    public function test_health_check_returns_database_up_on_successful_connection()
    {
        $this->login();

        ActiveCall::factory()->count(3)->create();

        $this->get(route('web.db-health-check'))
            ->assertOk()
            ->assertExactJson(['databaseup']);
    }

    public function test_health_check_returns_500_on_database_connection_error()
    {
        $this->login();

        DB::shouldReceive('connection->select')
            ->andThrow(new \Exception('DB Error'));

        $this->get(route('web.db-health-check'))
            ->assertStatus(500)
            ->assertExactJson([]);
    }
}

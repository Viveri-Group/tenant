<?php

namespace Tests\Feature\API\Health;

use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_success_when_db_is_up(): void
    {
        $this->login();

        $this->getJson(route('api.health-check'))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_health_check_returns_failure_when_db_is_down(): void
    {
        $this->withoutExceptionHandling();

        $this->login();

        DB::shouldReceive('select')
            ->once()
            ->andThrow(new Exception('DB error'));

        $this->getJson(route('api.health-check'))
            ->assertOk()
            ->assertJson([
                'success' => false,
            ]);
    }
}

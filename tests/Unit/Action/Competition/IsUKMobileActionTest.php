<?php

namespace Tests\Unit\Action\Competition;

use App\Action\Helpers\IsUKMobileAction;
use Tests\TestCase;

class IsUKMobileActionTest extends TestCase
{
    protected IsUKMobileAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new IsUKMobileAction();
    }

    public function test_it_accepts_valid_uk_mobile_starting_with_07()
    {
        $this->assertTrue($this->action->handle('07123456789'));
        $this->assertTrue($this->action->handle('07987654321'));
    }

    public function test_it_accepts_valid_uk_mobile_starting_with_447()
    {
        $this->assertTrue($this->action->handle('447123456789'));
        $this->assertTrue($this->action->handle('447987654321'));
    }

    public function test_it_accepts_valid_numbers_with_spaces_or_symbols()
    {
        $this->assertTrue($this->action->handle('+44 7123 456 789'));
        $this->assertTrue($this->action->handle('(07123) 456-789'));
        $this->assertTrue($this->action->handle('+447123-456-789'));
    }

    public function test_it_rejects_invalid_prefix()
    {
        $this->assertFalse($this->action->handle('06123456789'));  // wrong prefix
        $this->assertFalse($this->action->handle('441234567890')); // not 447 or 07
    }

    public function test_it_rejects_numbers_with_invalid_length()
    {
        $this->assertFalse($this->action->handle('0712345678'));   // 10 digits
        $this->assertFalse($this->action->handle('071234567890')); // 12 digits, wrong prefix
        $this->assertFalse($this->action->handle('44712345678'));  // too short for 447
    }

    public function test_it_rejects_numbers_with_non_numeric_characters_after_cleaning()
    {
        $this->assertFalse($this->action->handle('07123O56789'));  // contains letter O
        $this->assertFalse($this->action->handle('44712A456789')); // contains letter A
    }

    public function test_it_rejects_completely_invalid_input()
    {
        $this->assertFalse($this->action->handle(''));
        $this->assertFalse($this->action->handle('hello world'));
        $this->assertFalse($this->action->handle('+44-abc-test'));
    }
}

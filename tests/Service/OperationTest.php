<?php

declare(strict_types=1);

namespace Bank\Tests\Service;

use PHPUnit\Framework\TestCase;
use Bank\Service\Operation;

class OperationTest extends TestCase
{
    private $op;

    public function setUp():void
    {
        $this->op = Operation::getInstance(realpath('input.csv'));
    }

    public function testAdd()
    {
		$myResult = $this->op->calculateAllCommissions();
		$expectedResult = [0.60,3.00,0.00,0.06,1.50,0,0.69,0.30,0.30,3.00,0.00,0.00,8608];
        $this->assertEquals(
            $expectedResult,
            $myResult
        );
    }

}

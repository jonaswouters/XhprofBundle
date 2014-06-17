<?php

namespace Jns\Bundle\XhprofBundle\Tests;

use Prophecy\Prophet;

abstract class ProphecyTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Prophet
     */
    protected $prophet;

    public function setUp()
    {
        $this->prophet = new Prophet;
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    public function getProphecy($className)
    {
        return $this->prophet->prophesize($className);
    }
}
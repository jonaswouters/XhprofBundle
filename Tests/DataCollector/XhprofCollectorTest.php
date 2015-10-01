<?php

namespace Jns\Bundle\XhprofBundle\Tests\DataCollector;

use Jns\Bundle\XhprofBundle\DataCollector\XhprofCollector;

class XhprofCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XhprofCollector
     */
    private $collector;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Doctrine\Common\Persistence\ManagerRegistry
     */
    private $doctrine;

    public function setUp() {
        parent::setUp();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')->disableOriginalConstructor()->getMock();
        $this->doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->collector = new XhprofCollectorTestable($this->container, null, $this->doctrine);
    }

    /**
     * @dataProvider data_createRun
     */
    public function test_createRun($enable_xhgui, $manager_class, $runs_class) {
        $this->container->method('get')->with('manager_registry')->will($this->returnValue($this->doctrine));
        $this->container->method('getParameter')->will($this->returnValueMap(array(
            array('jns_xhprof.enable_xhgui', $enable_xhgui),
            array('jns_xhprof.entity_manager', 'entity_manager'),
            array('jns_xhprof.manager_registry', 'manager_registry'),
        )));
        $this->doctrine->method('getManager')->with('entity_manager')->will($this->returnValue(!$manager_class ?: $this->getMock($manager_class)));
        $xhprof_runs = $this->collector->createRun('serverName', 'uri');
        $this->assertInstanceOf($runs_class, $xhprof_runs);
    }

    public function data_createRun() {
        return array(
            array(false, null, '\XHProfRuns_Default'),
            array(true, '\Doctrine\ODM\MongoDB\DocumentManager', '\Jns\Bundle\XhprofBundle\Document\XhguiRuns'),
            array(true, '\Doctrine\ORM\EntityManager', '\Jns\Bundle\XhprofBundle\Entity\XhguiRuns'),
        );
    }
}

class XhprofCollectorTestable extends XhprofCollector
{
    public function createRun($serverName, $uri) {
        return parent::createRun($serverName, $uri);
    }
}
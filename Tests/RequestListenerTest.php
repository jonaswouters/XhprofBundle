<?php

namespace Jns\Bundle\XhprofBundle\Tests;

use Jns\Bundle\XhprofBundle\RequestListener;
use Jns\Bundle\XhprofBundle\DataCollector\XhprofCollector;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListenerTest extends ProphecyTestCase
{
    /**
     * @var XhprofCollector $collector
     */
    private $collector;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var GetResponseEvent $getResponseEvent
     */
    private $getResponseEvent;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var ParameterBag $query
     */
    private $query;

    /**
     * @var FilterResponseEvent $filterResponseEvent
     */
    private $filterResponseEvent;

    /**
     * @var Response $response
     */
    private $response;

    /**
     * @var ParameterBag $headers
     */
    private $headers;

    /**
     * @var RequestListener $requestListener
     */
    private $requestListener;

    public function setUp()
    {
        parent::setUp();

        // Init tested class
        $this->collector = $this->getProphecy('Jns\Bundle\XhprofBundle\DataCollector\XhprofCollector');
        $this->container = $this->getProphecy('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->requestListener = new RequestListener($this->collector->reveal(), $this->container->reveal());

        // Init some common objects
        $this->getResponseEvent     = $this->getProphecy('Symfony\Component\HttpKernel\Event\GetResponseEvent');
        $this->request              = $this->getProphecy('Symfony\Component\HttpFoundation\Request');
        $this->query                = $this->getProphecy('Symfony\Component\HttpFoundation\ParameterBag');
        $this->filterResponseEvent  = $this->getProphecy('Symfony\Component\HttpKernel\Event\FilterResponseEvent');
        $this->response             = $this->getProphecy('Symfony\Component\HttpFoundation\Response');
        $this->headers              = $this->getProphecy('Symfony\Component\HttpFoundation\ParameterBag');

        $this->getResponseEvent->getRequest()
            ->willReturn($this->request->reveal());

        $this->filterResponseEvent->getRequest()
            ->willReturn($this->request->reveal());

        $this->filterResponseEvent->getResponse()
            ->willReturn($this->response->reveal());

        $this->request->query = $this->query->reveal();
        $this->response->headers = $this->headers->reveal();
    }

    ############################
    # onCoreRequest test suite #
    ############################

    public function test_on_core_request_without_master_request()
    {
        $this->getResponseEvent->getRequestType()
            ->willReturn('foo');

        $this->collector->startProfiling()
            ->shouldNotBeCalled();

        $this->requestListener->onCoreRequest($this->getResponseEvent->reveal());
    }

    public function test_on_core_request_with_request_query_argument_configured_but_not_provided()
    {
        $this->getResponseEvent->getRequestType()
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->container->getParameter('jns_xhprof.request_query_argument')
            ->willReturn('__xhprof');

        $this->query->get('__xhprof')
            ->willReturn(null);

        $this->collector->startProfiling()
            ->shouldNotBeCalled();

        $this->requestListener->onCoreRequest($this->getResponseEvent->reveal());
    }

    public function provider_on_core_request_with_hard_ignored_uri()
    {
        return array(
            array('http://foo.com/_wdt'),
            array('http://foo.com/_wdt/bar'),
            array('http://foo.com/_profiler'),
            array('http://foo.com/_profiler/bar'),
        );
    }

    /**
     * @dataProvider provider_on_core_request_with_hard_ignored_uri
     */
    public function test_on_core_request_with_hard_ignored_uri($uri)
    {
        $this->getResponseEvent->getRequestType()
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->container->getParameter('jns_xhprof.request_query_argument')
            ->willReturn(false);

        $this->request->getRequestUri()
            ->willReturn($uri);

        $this->collector->startProfiling()
            ->shouldNotBeCalled();

        $this->requestListener->onCoreRequest($this->getResponseEvent->reveal());
    }

    public function test_on_core_request_with_ignored_uri_pattern()
    {
        $this->getResponseEvent->getRequestType()
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->container->getParameter('jns_xhprof.request_query_argument')
            ->willReturn(false);

        $this->request->getRequestUri()
            ->willReturn('http://foo.com/amazing/ignored/uri');

        $this->container->getParameter('jns_xhprof.exclude_patterns')
            ->willReturn(array(
                '/ignored/',
            ));

        $this->collector->startProfiling()
            ->shouldNotBeCalled();

        $this->requestListener->onCoreRequest($this->getResponseEvent->reveal());
    }

    public function test_on_core_request_with_request_query_argument_configured_and_provided()
    {
        $this->getResponseEvent->getRequestType()
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);
        $this->getResponseEvent->getRequest()
            ->willReturn($this->request);

        $this->container->getParameter('jns_xhprof.request_query_argument')
            ->willReturn('__xhprof');

        $this->query->get('__xhprof')
            ->willReturn(true);

        $this->query->remove('__xhprof')
            ->shouldBeCalled();

        $this->request->getRequestUri()
            ->willReturn('http://foo.com/amazing/uri');

        $this->container->getParameter('jns_xhprof.exclude_patterns')
            ->willReturn(array(
                '/ignored/',
            ));

        $this->collector->startProfiling($this->request->reveal())
            ->shouldBeCalled();

        $this->requestListener->onCoreRequest($this->getResponseEvent->reveal());
    }

    #############################
    # onCoreResponse test suite #
    #############################

    public function test_on_core_response_without_master_request()
    {
        $this->filterResponseEvent->getRequestType()
            ->willReturn('foo');

        $this->collector->stopProfiling(Argument::any())
            ->shouldNotBeCalled();

        $this->requestListener->onCoreResponse($this->filterResponseEvent->reveal());
    }

    public function test_on_core_response_without_link_returned()
    {
        $this->filterResponseEvent->getRequestType()
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->request->getHost()
            ->willReturn('foo.com');

        $this->request->getUri()
            ->willReturn('/amazing/uri');

        $this->collector->stopProfiling('foo.com', '/amazing/uri')
            ->willReturn(false);

        $this->requestListener->onCoreResponse($this->filterResponseEvent->reveal());
    }

    public function test_on_core_response_without_headerName()
    {
        $this->filterResponseEvent->getRequestType()
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->request->getHost()
            ->willReturn('foo.com');

        $this->request->getUri()
            ->willReturn('/amazing/uri');

        $this->collector->stopProfiling('foo.com', '/amazing/uri')
            ->willReturn('link');

        $this->container->getParameter('jns_xhprof.response_header')
            ->willReturn(null);

        $this->requestListener->onCoreResponse($this->filterResponseEvent->reveal());
    }

    public function test_on_core_response()
    {
        $this->filterResponseEvent->getRequestType()
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->request->getHost()
            ->willReturn('foo.com');

        $this->request->getUri()
            ->willReturn('/amazing/uri');

        $this->collector->stopProfiling('foo.com', '/amazing/uri')
            ->willReturn('link');

        $this->container->getParameter('jns_xhprof.response_header')
            ->willReturn('header-name');

        $this->collector->getXhprofUrl()
            ->willReturn('http://xhprof.com/url');

        $this->headers->set('header-name', 'http://xhprof.com/url')
            ->shouldBeCalled();

        $this->requestListener->onCoreResponse($this->filterResponseEvent->reveal());
    }
}

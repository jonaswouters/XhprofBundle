<?php

namespace Jns\Bundle\XhprofBundle;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * RequestListener.
 *
 * The handle method must be connected to the core.request event.
 *
 * @author Jonas Wouters <hello@jonaswouters.be>
 */
class RequestListener
{
    protected $collector;
    protected $request;

    public function __construct(DataCollector\XhprofCollector $collector, Request $request)
    {
        $this->collector = $collector;
        $this->request = $request;
    }

    public function onCoreRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->collector->startProfiling();
        }
    }

    public function onCoreResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->collector->stopProfiling();
        }
    }
}

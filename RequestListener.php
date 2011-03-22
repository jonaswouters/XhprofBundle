<?php

namespace Jns\Bundle\XhprofBundle;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\Event;
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
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function onCoreRequest(GetResponseEvent $event)
    {
        xhprof_enable();
        if ($this->logger)
        {
            $this->logger->debug('Enabled XHProf');
        }
    }
}

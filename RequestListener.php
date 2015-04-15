<?php

namespace Jns\Bundle\XhprofBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * A web request listener to profile requests.
 *
 * The methods must be connected to the kernel.request and kernel.response
 * events.
 *
 * @author Jonas Wouters <hello@jonaswouters.be>
 */
class RequestListener
{
    protected $collector;
    private $container;

    public function __construct(DataCollector\XhprofCollector $collector, ContainerInterface $container)
    {
        $this->collector = $collector;
        $this->container = $container;
    }

    /**
     * Start the profiler if
     * - this is not a sub-request but the master request
     * - we are not on the _wdt or _profiler url
     * - if the query argument name is configured, only if it is present in the request
     * - if the url does not match one of the exclude patterns
     *
     * @param GetResponseEvent $event
     */
    public function onCoreRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        $requestQueryArgument = $this->container->getParameter('jns_xhprof.request_query_argument');
        if ($requestQueryArgument && is_null($request->query->get($requestQueryArgument))) {
            return;
        } elseif ($requestQueryArgument) {
            $request->query->remove($requestQueryArgument);
        }

        $uri = $request->getRequestUri();

        if (false !== strpos($uri, "_wdt") || false !== strpos($uri, "_profiler")) {
            return;
        }

        if ($excludePatterns = $this->container->getParameter('jns_xhprof.exclude_patterns')) {
            foreach ($excludePatterns as $exclude) {
                if (preg_match('@' . $exclude . '@', $uri)) {
                    return;
                }
            }
        }

        $this->collector->startProfiling();

        $collector = $this->collector;

        register_shutdown_function(function () use ($collector, $event) {
            if ($collector->isCollecting()) {
                $request = $event->getRequest();
                $collector->stopProfiling($request->getHost(), $request->getUri());
            }
        });
    }

    /**
     * Trigger ending the profiling if we end the master request. If the debug
     * toolbar is active, this happens after XhprofCollector::collect, and thus
     * the collector will return false.
     *
     * If the collector returns something, we put that into the special header
     * to let the user identify the profiler run.
     *
     * @param FilterResponseEvent $event
     */
    public function onCoreResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        $request = $event->getRequest();
        $link = $this->collector->stopProfiling($request->getHost(), $request->getUri());

        if (false === $link) {
            return;
        }

        $headerName = $this->container->getParameter('jns_xhprof.response_header');
        if ($headerName) {
            $event->getResponse()->headers->set($headerName, $this->collector->getXhprofUrl());
        }
    }
}

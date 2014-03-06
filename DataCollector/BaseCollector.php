<?php

namespace Jns\Bundle\XhprofBundle\DataCollector;

use Symfony\Component\HttpKernel\Log\LoggerInterface as HttpKernelLoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseCollector extends DataCollector
{
    protected $container;
    protected $logger;
    protected $runId;
    protected $collecting = false;

    protected static $run;

    public function __construct(ContainerInterface $container, $logger = null)
    {
        $this->container = $container;

        if ($logger !== null && !$logger instanceof HttpKernelLoggerInterface && !$logger instanceof PsrLoggerInterface) {
            throw new \InvalidArgumentException('Logger must be an instance of Symfony\Component\HttpKernel\Log\LoggerInterface or Psr\Log\LoggerInterface');
        }

        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * Prepare data for the debug toolbar.
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if (!$this->runId) {
            $this->stopProfiling($request->getHost(), $request->getUri(), $request);
        }
    }

    /**
     * Start profiling with probability according to sample size.
     *
     * @return boolean whether profiling was started or not.
     */
    public function startProfiling()
    {
        if (! $this->container->getParameter('jns_xhprof.enabled')) {
            return false;
        }

        if (mt_rand(1, $this->container->getParameter('jns_xhprof.sample_size')) != 1) {
            return false;
        }

        $this->collecting = true;
        xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

        if ($this->logger) {
            $this->logger->debug('Enabled XHProf');
        }

        return true;
    }

    /**
     * Stop profiling
     *
     * @param         $serverName
     * @param         $uri
     * @param Request $request
     *
     * @return bool
     */
    public function stopProfiling($serverName, $uri, Request $request = null)
    {
        if (!$this->collecting) {
            return false;
        }

        $this->collecting = false;
        self::$run = self::$run ?: xhprof_disable();

        if ($this->logger) {
            $this->logger->debug('Disabled XHProf');
        }

        $this->runId = $this->saveRun(self::$run, $serverName, $uri, $request);

        $this->data = array(
            'xhprof' => $this->runId,
            'url' => $this->container->getParameter('jns_xhprof.location_web'),
        );

        return $this->data['xhprof'];
    }

    /**
     * Save the run to a storage mechanism
     *
     * @param array   $data
     * @param         $serverName
     * @param         $uri
     * @param Request $request
     *
     * @return null|string
     */
    abstract public function saveRun(array $data = array(), $serverName, $uri, Request $request = null);

    /**
     * {@inheritdoc}
     */
    abstract public function getName();

    /**
     * Gets the run id.
     *
     * @return integer The run id
     */
    public function getXhprof()
    {
        return $this->data['xhprof'];
    }

    /**
     * Gets the XHProf url.
     *
     * @return integer The XHProf url
     */
    public function getUrl()
    {
        return $this->data['url'] . '?run=' . $this->data['xhprof'];
    }

    /**
     * Check whether this request was profiled. Used for the debug toolbar.
     *
     * @return boolean
     */
    public function isProfiling()
    {
        return $this->data['xhprof']  ? true : false;
    }
}

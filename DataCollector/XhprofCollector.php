<?php

namespace Jns\Bundle\XhprofBundle\DataCollector;

// supports 2.0, 2.1 LoggerInterface
use Symfony\Component\HttpKernel\Log\LoggerInterface as HttpKernelLoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

/**
 * XhprofDataCollector.
 *
 * @author Jonas Wouters <hello@jonaswouters.be>
 */
class XhprofCollector extends DataCollector
{
    protected $container;
    protected $logger;
    protected $runId;
    protected $doctrine;
    protected $collecting = false;

    public function __construct(ContainerInterface $container, $logger = null, DoctrineRegistry $doctrine = null)
    {
        $this->container = $container;

        if ($logger !== null && !$logger instanceof HttpKernelLoggerInterface && !$logger instanceof PsrLoggerInterface) {
            throw new \InvalidArgumentException('Logger must be an instance of Symfony\Component\HttpKernel\Log\LoggerInterface or Psr\Log\LoggerInterface');
        }

        $this->logger    = $logger;
        $this->doctrine  = $doctrine;
        $this->data['xhprof'] = null;
        $this->data['source'] = null;
        $this->data['xhprof_url'] = null;
    }

    /**
     * {@inheritdoc}
     *
     * Prepare data for the debug toolbar.
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if (!$this->runId) {
            $this->stopProfiling($request->getHost(), $request->getUri());
        }
    }

    /**
     * Start profiling with probability according to sample size.
     *
     * @return boolean whether profiling was started or not.
     */
    public function startProfiling()
    {
        if (mt_rand(1, $this->container->getParameter('jns_xhprof.sample_size')) != 1) {
            return false;
        }

        $this->collecting = true;
        xhprof_enable($this->getFlags());

        if ($this->logger) {
            $this->logger->debug('Enabled XHProf');
        }

        return true;
    }

    /**
     * Calculate the flags for xhprof_enable
     *
     * @return int
     */
    private function getFlags()
    {
        $flags = XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY;
        if ($this->container->getParameter('jns_xhprof.skip_builtin_functions') === true) {
            $flags |= XHPROF_FLAGS_NO_BUILTINS;
        }

        return $flags;
    }

    /**
     * Stop profiling if we where profiling.
     *
     * @param string $serverName The server name the request is running on, or cli for command line.
     * @param string $uri        The requested uri / command name.
     *
     * @return bool
     */
    public function stopProfiling($serverName, $uri)
    {
        if (!$this->collecting) {
            return $this->data['xhprof'] ? $this->data['xhprof'] : false;
        }

        $this->collecting = false;

        $enableXhgui = $this->container->getParameter('jns_xhprof.enable_xhgui');

        $xhprof_data = xhprof_disable();

        if ($this->logger) {
            $this->logger->debug('Disabled XHProf');
        }

        $xhprof_runs = new \XHProfRuns_Default();

        $source = null;

        if ($enableXhgui) {
            $this->runId = $this->saveProfilingDataToDB($xhprof_data, $uri, $serverName);
        } else {
            $source = $this->sanitizeUriForSource($uri);

            $this->runId = $xhprof_runs->save_run($xhprof_data, $source);
        }


        $this->data = array(
            'xhprof' => $this->runId,
            'source' => $source,
            'xhprof_url' => $this->container->getParameter('jns_xhprof.location_web'),
        );

        return $this->data['xhprof'];
    }

    /**
     * Sanitize an uri to use it as source
     *
     * @param  string $uri
     * @return string
     */
    private function sanitizeUriForSource($uri)
    {
        $uri = preg_replace('/[\/]+/', '~', $uri);

        return preg_replace('/[^\w~\-_]+/', '-', $uri);
    }

    /**
     * This function saves the profiling data as well as some additional data to a profiling database.
     *
     * @param  array $xhprof_data
     * @param string $uri
     * @param string $serverName
     * @throws \Exception if doctrine was not injected correctly
     * @return string   Returns the run id for the saved XHProf run
     */
    private function saveProfilingDataToDB($xhprof_data, $uri, $serverName)
    {
        $pmu = isset($xhprof_data['main()']['pmu']) ? $xhprof_data['main()']['pmu'] : '';
        $wt  = isset($xhprof_data['main()']['wt'])  ? $xhprof_data['main()']['wt']  : '';
        $cpu = isset($xhprof_data['main()']['cpu']) ? $xhprof_data['main()']['cpu'] : '';

        if (empty($this->doctrine)) {
            throw new \Exception("Trying to save to database, but Doctrine was not set correctly");
        }

        $runId = uniqid();

        $em = $this->doctrine->getManager($this->container->getParameter('jns_xhprof.entity_manager'));
        $entityClass  = $this->container->getParameter('jns_xhprof.entity_class');

        $xhprofDetail = new $entityClass();
        $xhprofDetail
            ->setId($runId)
            ->setUrl($uri)
            ->setCanonicalUrl($this->getCanonicalUrl($uri))
            ->setServerName($serverName)
            ->setPerfData(gzcompress(serialize(($xhprof_data))))
            ->setCookie(serialize($_COOKIE))
            ->setPost(serialize($_POST))
            ->setGet(serialize($_GET))
            ->setPmu($pmu)
            ->setWt($wt)
            ->setCpu($cpu)
            ->setServerId(getenv('SERVER_NAME'))
            ->setAggregateCallsInclude('')
            ->setTimestamp(new \DateTime())
            ;

        $em->persist($xhprofDetail);
        $em->flush();

        return $runId;
    }

    /**
     * Return the canonical URL for the passed in one.
     *
     * @param  String $url
     * @return String
     */
    protected function getCanonicalUrl($url)
    {
        if ($url[0] == '#') {
            $url = substr($url, 1, -1);

            if (substr($url, -1) == '$') {
                $url = substr($url, 0, -1);
            }
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'xhprof';
    }

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
     * @return string The XHProf url
     */
    public function getXhprofUrl()
    {
        return $this->data['xhprof_url'] . '?run=' . $this->data['xhprof'] . '&source='.$this->data['source'];
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

    /**
     * Check is the collector is currently running
     * @return bool
     */
    public function isCollecting()
    {
        return $this->collecting === true;
    }
}

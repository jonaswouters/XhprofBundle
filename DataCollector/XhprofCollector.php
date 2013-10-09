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
use Jns\Bundle\XhprofBundle\Entity\XhprofDetail;

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
    protected $profiling = false;

    public function __construct(ContainerInterface $container, $logger = null, DoctrineRegistry $doctrine = null)
    {
        $this->container = $container;

        if (!$logger instanceof HttpKernelLoggerInterface && !$logger instanceof PsrLoggerInterface) {
            throw new \InvalidArgumentException('Logger must be an instance of Symfony\Component\HttpKernel\Log\LoggerInterface or Psr\Log\LoggerInterface');
        }

        $this->logger    = $logger;
        $this->doctrine  = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if (!$this->runId) {
            $this->stopProfiling($response);
        }
    }

    public function startProfiling()
    {
        if (PHP_SAPI == 'cli') {
            $_SERVER['REMOTE_ADDR'] = null;
            $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
        }

        if (false !== strpos($_SERVER['REQUEST_URI'], "_wdt") || false !== strpos($_SERVER['REQUEST_URI'], "_profiler")) {
            $this->profiling = false;
            return;
        }

        if (mt_rand(1, $this->container->getParameter('jns_xhprof.sample_size')) != 1) {
            $this->profiling = false;
            return;
        }

        $this->profiling = true;
        xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

        if ($this->logger) {
            $this->logger->debug('Enabled XHProf');
        }
    }

    public function stopProfiling(Response $response)
    {
        if (!$this->profiling) {
            return;
        }

        $this->profiling = false;

        $enableXhgui = $this->container->getParameter('jns_xhprof.enable_xhgui');

        $xhprof_data = xhprof_disable();

        if ($this->logger) {
            $this->logger->debug('Disabled XHProf');
        }

        $xhprof_runs = new \XHProfRuns_Default();

        if ($enableXhgui) {
            $this->runId = $this->saveProfilingDataToDB($xhprof_data);
        } else {
            $this->runId = $xhprof_runs->save_run($xhprof_data, "Symfony");
        }


        $this->data = array(
            'xhprof' => $this->runId,
            'xhprof_url' => $this->container->getParameter('jns_xhprof.location_web'),
        );
        $headerName = $this->container->getParameter('jns_xhprof.response_header');
        if ($headerName) {
            $response->headers->set($headerName, $this->getXhprofUrl());
        }
    }

    /**
     * This function saves the profiling data as well as some additional data to a profiling database.
     *
     * @param  array $xhprof_data
     * @throws \Exception if doctrine was not injected correctly
     * @return string   Returns the run id for the saved XHProf run
     */
    private function saveProfilingDataToDB($xhprof_data)
    {
        $url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
        $sname = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

        $pmu = isset($xhprof_data['main()']['pmu']) ? $xhprof_data['main()']['pmu'] : '';
        $wt  = isset($xhprof_data['main()']['wt'])  ? $xhprof_data['main()']['wt']  : '';
        $cpu = isset($xhprof_data['main()']['cpu']) ? $xhprof_data['main()']['cpu'] : '';

        if (empty($this->doctrine)) {
            throw new \Exception("Trying to save to database, but Doctrine was not set correctly");
        }

        $runId = uniqid();

        $em = $this->doctrine->getManager($this->container->getParameter('jns_xhprof.entity_manager'));
        $xhprofDetail = new XhprofDetail();
        $xhprofDetail
            ->setId($runId)
            ->setUrl($url)
            ->setCanonicalUrl($this->getCanonicalUrl($url))
            ->setServerName($sname)
            ->setPerfData(gzcompress(serialize(($xhprof_data))))
            ->setCookie(serialize($_COOKIE))
            ->setPost(serialize($_POST))
            ->setGet(serialize($_GET))
            ->setPmu($pmu)
            ->setWt($wt)
            ->setCpu($cpu)
            ->setServerId(getenv('SERVER_NAME'))
            ->setAggregateCallsInclude('')
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
     * @return integer The XHProf url
     */
    public function getXhprofUrl()
    {
        return $this->data['xhprof_url'] . '?run=' . $this->data['xhprof'] . '&source=Symfony';
    }

    /**
     * Gets the XHProf url.
     *
     * @return integer The XHProf url
     */
    public function isProfiling()
    {
        return $this->data['xhprof']  ? true : false;
    }
}


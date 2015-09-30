<?php

namespace Jns\Bundle\XhprofBundle\DataCollector;

// supports 2.0, 2.1 LoggerInterface
use Symfony\Component\HttpKernel\Log\LoggerInterface as HttpKernelLoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Jns\Bundle\XhprofBundle\Document\XhguiRuns as XhguiRuns_Document;
use Jns\Bundle\XhprofBundle\Entity\XhguiRuns as XhguiRuns_Entity;
use Doctrine\ODM\MongoDB\DocumentManager;

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
    protected $collectingRequest;

    public function __construct(ContainerInterface $container, $logger = null, ManagerRegistry $doctrine = null)
    {
        $this->container = $container;

        if ($logger !== null && !$logger instanceof HttpKernelLoggerInterface && !$logger instanceof PsrLoggerInterface) {
            throw new \InvalidArgumentException('Logger must be an instance of Symfony\Component\HttpKernel\Log\LoggerInterface or Psr\Log\LoggerInterface');
        }

        $this->logger    = $logger;
        $this->doctrine  = $doctrine;
        $this->data['xhprof_extension_exists'] = function_exists('xhprof_enable');
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
        if (!$this->runId && $request === $this->collectingRequest) {
            $this->stopProfiling($request->getHost(), $request->getUri());
        }
    }

    /**
     * Start profiling with probability according to sample size.
     *
     * @return boolean whether profiling was started or not.
     */
    public function startProfiling(Request $request = null)
    {
        if (!function_exists('xhprof_enable') || mt_rand(1, $this->container->getParameter('jns_xhprof.sample_size')) != 1) {
            return false;
        }

        $this->collecting = true;
        $this->collectingRequest = $request;
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
        if (isset($this->data['xhprof'])) {
            return $this->data['xhprof'];
        }

        if (!$this->collecting) {
            return $this->data['xhprof'] ? $this->data['xhprof'] : false;
        }

        $this->collecting = false;

        $xhprof_data = xhprof_disable();

        if ($this->logger) {
            $this->logger->debug('Disabled XHProf');
        }

        $xhprof_runs = $this->createRun($serverName, $uri);
        $source = null;

        if ($xhprof_runs instanceof \XHProfRuns_Default) {
            $source = $this->sanitizeUriForSource($uri);
        }

        $this->runId = $xhprof_runs->save_run($xhprof_data, $source);

        $this->data = array(
            'xhprof' => $this->runId,
            'source' => $source,
        );

        if ($xhprof_runs instanceof XhguiRuns_Document) {
            $this->data['xhprof_url'] = $this->container->getParameter('jns_xhprof.location_web') . '/run/view?id=' . $this->data['xhprof'];
        } else {
            $this->data['xhprof_url'] = $this->container->getParameter('jns_xhprof.location_web') . '?run=' . $this->data['xhprof'] . '&source='.$this->data['source'];
        }

        return $this->data['xhprof'];
    }

    /**
     * @return \iXHProfRuns
     */
    protected function createRun($serverName, $uri) {
        $enableXhgui = $this->container->getParameter('jns_xhprof.enable_xhgui');
        if ($enableXhgui) {
            $managerRegistry = $this->container->get($this->container->getParameter('jns_xhprof.manager_registry'));
            $objectManager = $managerRegistry->getManager($this->container->getParameter('jns_xhprof.entity_manager'));
            if ($objectManager instanceof DocumentManager) {
                $xhprof_runs = new XhguiRuns_Document($objectManager);
            } else {
                $xhprof_runs = new XhguiRuns_Entity($serverName, $uri);
                $xhprof_runs->setContainer($this->container);
            }
        } else {
            $xhprof_runs = new \XHProfRuns_Default();
        }
        return $xhprof_runs;
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
     * Gets the XHProf extension exists or not.
     *
     * @return boolean Extension exists or not
     */
    public function getXhprofExtensionExists()
    {
        return $this->data['xhprof_extension_exists'];
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
        return $this->data['xhprof_url'];
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

<?php


namespace Jns\Bundle\XhprofBundle\DataCollector;


use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use XHProfRuns_Default;

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
    protected $profiling = false;

    public function __construct(ContainerInterface $container, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if (!$this->runId) {
            $this->stopProfiling();
        }

        $this->data = array(
            'xhprof' => $this->runId,
            'xhprof_url' => $this->container->getParameter('jns_xhprof.location_web'),
        );
    }

    public function startProfiling()
    {
        $this->profiling = true;
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

        if ($this->logger) {
            $this->logger->debug('Enabled XHProf');
        }
    }

    public function stopProfiling()
    {
        global $_xhprof;

        if (!$this->profiling) {
            return;
        }

        $this->profiling = false;

        require_once $this->container->getParameter('jns_xhprof.location_config');
        require_once $this->container->getParameter('jns_xhprof.location_lib');
        require_once $this->container->getParameter('jns_xhprof.location_runs');

        $xhprof_data = xhprof_disable();

        if ($this->logger) {
            $this->logger->debug('Disabled XHProf');
        }

        $xhprof_runs = new XHProfRuns_Default();
        $this->runId = $xhprof_runs->save_run($xhprof_data, "Symfony");
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
}

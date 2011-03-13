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

        require_once $this->container->getParameter('jns_xhprof.location.lib');
        require_once $this->container->getParameter('jns_xhprof.location.runs');

        $xhprof_data = xhprof_disable();

        if ($this->logger)
        {
            $this->logger->debug('Disabled XHProf');
        }

        $xhprof_runs = new XHProfRuns_Default();
        $run_id = $xhprof_runs->save_run($xhprof_data, "Symfony");
        
        $this->data = array(
            'xhprof' => $run_id,
            'xhprof_url' => $this->container->getParameter('jns_xhprof.location.web'),
        );
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

<?php

namespace Jns\Bundle\XhprofBundle\DataCollector;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use XHProfRuns_Default;
use Symfony\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

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

    public function __construct(ContainerInterface $container, LoggerInterface $logger = null, DoctrineRegistry $doctrine = null)
    {
        $this->container = $container;
        $this->logger    = $logger;
        $this->doctrine  = $doctrine;
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
        if (PHP_SAPI == 'cli') {
            $_SERVER['REMOTE_ADDR'] = null;
            $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
        }

        if (false !== strpos($_SERVER['REQUEST_URI'], "_wdt") || false !== strpos($_SERVER['REQUEST_URI'], "_profiler")) {
            $this->profiling = false;
            
            return;
        }

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

        //require_once $this->container->getParameter('jns_xhprof.location_config');
        require_once $this->container->getParameter('jns_xhprof.location_lib');
        require_once $this->container->getParameter('jns_xhprof.location_runs');

        $xhprof_data = xhprof_disable();

        if ($this->logger) {
            $this->logger->debug('Disabled XHProf');
        }

        $xhprof_runs = new XHProfRuns_Default();

        if ($this->container->getParameter('jns_xhprof.enable_xhgui')) {
            $this->saveProfilingDataToDB($xhprof_data);
        } else {
            $this->runId = $xhprof_runs->save_run($xhprof_data, "Symfony");    
        }   
    }

    /**
     * This function saves the profiling data as well as some additional data to a profiling database.
     * 
     * @param  array $xhprof_data
     * @throws \Exception if doctrine was not injected correctly
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

        $em = $this->doctrine->getEntityManager($this->container->getParameter('jns_xhprof.entity_manager'));

        $connection = $em->getConnection();
        $sql = 'INSERT INTO xhprof (`id`, `url`, `c_url`, `timestamp`, `server name`, `perfdata`, `type`, `cookie`, `post`, `get`, `pmu`, `wt`, `cpu`, `server_id`, `aggregateCalls_include`) 
                     VALUES (:run_id, :url, :canonical_url, null, :server_name, :perfdata, 0, :cookie, :post, :get, :pmu, :wt, :cpu, :server_id, \'\');';

        $this->runId = uniqid();

        $preparedStatement = $connection->prepare($sql);
        
        $preparedStatement->bindValue(':run_id', $this->runId);
        $preparedStatement->bindValue(':url', $url);
        $preparedStatement->bindValue(':canonical_url', $this->getCanonicalUrl($url));
        $preparedStatement->bindValue(':server_name', $sname);
        $preparedStatement->bindValue(':perfdata', gzcompress(json_encode($xhprof_data), 2));
        $preparedStatement->bindValue(':cookie', json_encode($_COOKIE));
        $preparedStatement->bindValue(':post', json_encode($_POST));
        $preparedStatement->bindValue(':get', json_encode($_GET));
        $preparedStatement->bindValue(':pmu', $pmu);
        $preparedStatement->bindValue(':wt', $wt);
        $preparedStatement->bindValue(':cpu', $cpu);
        $preparedStatement->bindValue(':server_id', getenv('SERV_NAME'));
        $preparedStatement->execute();
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
}

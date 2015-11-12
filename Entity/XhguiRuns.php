<?php

namespace Jns\Bundle\XhprofBundle\Entity;

use iXHProfRuns;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class XhguiRuns implements iXHProfRuns, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Sets the Container associated with this Controller.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function __construct($serverName, $uri) {
    	$this->serverName = $serverName;
    	$this->uri = $uri;
    }

    /**
     * {@inheritDoc}
     */
    public function get_run($run_id, $type, &$run_desc) {
        throw new \Exception('not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function save_run($xhprof_data, $type, $run_id = null) {
        $pmu = isset($xhprof_data['main()']['pmu']) ? $xhprof_data['main()']['pmu'] : '';
        $wt  = isset($xhprof_data['main()']['wt'])  ? $xhprof_data['main()']['wt']  : '';
        $cpu = isset($xhprof_data['main()']['cpu']) ? $xhprof_data['main()']['cpu'] : '';

        $doctrine = $this->container->get('doctrine');
        if (empty($doctrine)) {
            throw new \Exception("Trying to save to database, but Doctrine was not set correctly");
        }

        $runId = uniqid();

        $em = $doctrine->getManager($this->container->getParameter('jns_xhprof.entity_manager'));
        $entityClass  = $this->container->getParameter('jns_xhprof.entity_class');

        $xhprofDetail = new $entityClass();
        $xhprofDetail
            ->setId($runId)
            ->setUrl($this->uri)
            ->setCanonicalUrl($this->getCanonicalUrl($this->uri))
            ->setServerName($this->serverName)
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
}
?>
